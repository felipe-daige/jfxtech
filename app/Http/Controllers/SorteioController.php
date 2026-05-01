<?php

namespace App\Http\Controllers;

use App\Mail\SorteioParticipationConfirmedMail;
use App\Models\Sorteio;
use App\Models\SorteioParticipante;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class SorteioController extends Controller
{
    private const DEFAULT_RANDOM_NUMBER_POOL_SIZE = 99999;

    private const RANDOM_NUMBER_ATTEMPTS = 100;

    public function index()
    {
        $sorteio = Sorteio::query()
            ->where('ativo', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderByRaw('ends_at IS NULL')
            ->orderBy('ends_at')
            ->latest('id')
            ->first();

        if (! $sorteio) {
            return view('site.sorteios.indisponivel');
        }

        return $this->renderSorteio($sorteio);
    }

    public function show(Sorteio $sorteio)
    {
        return $this->renderSorteio($sorteio);
    }

    public function participar(Request $request, Sorteio $sorteio)
    {
        if (! $sorteio->inscricoesAbertas()) {
            return back()
                ->withInput()
                ->with('error', 'As inscricoes deste sorteio nao estao abertas.');
        }

        if (Auth::check()) {
            $participacao = SorteioParticipante::where('sorteio_id', $sorteio->id)
                ->where('user_id', Auth::id())
                ->first();

            if ($participacao) {
                return redirect()
                    ->route('site.sorteio.acompanhar', $sorteio)
                    ->with('success', 'Voce ja esta participando deste sorteio.');
            }
        }

        $request->merge([
            'cpf' => $this->digitsOnly($request->input('cpf')),
            'phone' => trim((string) $request->input('phone')),
            'instagram_username' => $this->normalizeInstagram($request->input('instagram_username')),
            'instagram_friend_1' => $this->normalizeInstagram($request->input('instagram_friend_1')),
            'instagram_friend_2' => $this->normalizeInstagram($request->input('instagram_friend_2')),
        ]);

        $validated = $this->validateParticipation($request, $sorteio);

        $participacao = DB::transaction(function () use ($request, $sorteio, $validated) {
            $lockedSorteio = Sorteio::whereKey($sorteio->id)->lockForUpdate()->firstOrFail();

            if (! $lockedSorteio->inscricoesAbertas()) {
                throw ValidationException::withMessages([
                    'sorteio' => 'As inscricoes deste sorteio nao estao abertas.',
                ]);
            }

            $user = Auth::user();

            if (! $user) {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'cpf' => $validated['cpf'],
                    'password' => Hash::make($validated['password']),
                ]);

                Auth::login($user);
                $request->session()->regenerate();
            } else {
                $user->update([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'cpf' => $validated['cpf'],
                ]);
            }

            $numero = $this->generateAvailableRandomNumber($lockedSorteio);

            return SorteioParticipante::create([
                'sorteio_id' => $lockedSorteio->id,
                'user_id' => $user->id,
                'numero' => $numero,
                'instagram_username' => $validated['instagram_username'],
                'instagram_friend_1' => $validated['instagram_friend_1'],
                'instagram_friend_2' => $validated['instagram_friend_2'],
                'status' => SorteioParticipante::STATUS_PENDENTE,
                'accepted_rules_at' => now(),
                'instagram_requirements_accepted_at' => now(),
                'marketing_opt_in_at' => $request->boolean('marketing_opt_in') ? now() : null,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
            ]);
        });

        $this->sendParticipationConfirmationMail($participacao);

        return redirect()
            ->route('site.sorteio.acompanhar', $sorteio)
            ->with('success', 'Participacao confirmada. Seu numero e '.$participacao->numeroFormatado().'.');
    }

    public function acompanhar(Sorteio $sorteio)
    {
        $participacao = SorteioParticipante::with(['sorteio.ganhador.user', 'user'])
            ->where('sorteio_id', $sorteio->id)
            ->where('user_id', Auth::id())
            ->first();

        if (! $participacao) {
            return redirect()
                ->route('site.sorteio.show', $sorteio)
                ->with('error', 'Voce ainda nao tem participacao neste sorteio.');
        }

        return view('site.sorteios.acompanhar', [
            'sorteio' => $sorteio->load(['ganhador.user', 'produto.imagens']),
            'participacao' => $participacao,
        ]);
    }

    public function minhasParticipacoes()
    {
        $participacoes = SorteioParticipante::with(['sorteio.ganhador.user', 'sorteio.produto.imagens'])
            ->where('user_id', Auth::id())
            ->latest('id')
            ->paginate(12);

        return view('site.sorteios.minhas', compact('participacoes'));
    }

    private function renderSorteio(Sorteio $sorteio)
    {
        $participacao = Auth::check()
            ? SorteioParticipante::where('sorteio_id', $sorteio->id)->where('user_id', Auth::id())->first()
            : null;

        return view('site.sorteios.show', [
            'sorteio' => $sorteio->load(['ganhador.user', 'produto.imagens']),
            'participacao' => $participacao,
        ]);
    }

    private function sendParticipationConfirmationMail(SorteioParticipante $participacao): void
    {
        $participacao->loadMissing(['sorteio', 'user']);

        if (! $participacao->user?->email) {
            return;
        }

        try {
            Mail::to($participacao->user->email)
                ->queue(new SorteioParticipationConfirmedMail($participacao));
        } catch (Throwable $exception) {
            Log::error('Sorteio participation confirmation email failed', [
                'sorteio_id' => $participacao->sorteio_id,
                'participacao_id' => $participacao->id,
                'user_id' => $participacao->user_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function validateParticipation(Request $request, Sorteio $sorteio): array
    {
        $userId = Auth::id();
        $emailUniqueRule = Rule::unique('users', 'email');
        $cpfUniqueRule = Rule::unique('users', 'cpf');

        if ($userId) {
            $emailUniqueRule->ignore($userId);
            $cpfUniqueRule->ignore($userId);
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', $emailUniqueRule],
            'phone' => ['required', 'string', 'max:20'],
            'cpf' => [
                'required',
                'digits:11',
                $cpfUniqueRule,
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $this->isValidCpf((string) $value)) {
                        $fail('Digite um CPF valido.');
                    }
                },
            ],
            'instagram_username' => [
                'required',
                'regex:/^[a-z0-9._]{1,30}$/',
                Rule::unique('sorteio_participantes', 'instagram_username')
                    ->where(fn ($query) => $query->where('sorteio_id', $sorteio->id)),
            ],
            'instagram_friend_1' => ['required', 'regex:/^[a-z0-9._]{1,30}$/', 'different:instagram_username', 'different:instagram_friend_2'],
            'instagram_friend_2' => ['required', 'regex:/^[a-z0-9._]{1,30}$/', 'different:instagram_username', 'different:instagram_friend_1'],
            'instagram_requirements' => ['required', 'accepted'],
            'rules' => ['required', 'accepted'],
            'marketing_opt_in' => ['nullable', 'accepted'],
        ];

        if (! Auth::check()) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        $validator = Validator::make($request->all(), $rules, [
            'name.required' => 'O nome e obrigatorio.',
            'email.required' => 'O e-mail e obrigatorio.',
            'email.email' => 'Digite um e-mail valido.',
            'email.unique' => 'Este e-mail ja esta cadastrado. Faca login para participar com esta conta.',
            'phone.required' => 'O telefone e obrigatorio.',
            'cpf.required' => 'O CPF e obrigatorio.',
            'cpf.digits' => 'Informe o CPF com 11 digitos.',
            'cpf.unique' => 'Este CPF ja esta cadastrado em outra conta.',
            'instagram_username.required' => 'Informe seu usuario do Instagram.',
            'instagram_username.regex' => 'Informe um usuario do Instagram valido.',
            'instagram_username.unique' => 'Este usuario do Instagram ja esta participando deste sorteio.',
            'instagram_friend_1.required' => 'Informe o primeiro amigo marcado.',
            'instagram_friend_1.regex' => 'Informe um usuario valido para o primeiro amigo.',
            'instagram_friend_1.different' => 'Os usuarios marcados devem ser diferentes.',
            'instagram_friend_2.required' => 'Informe o segundo amigo marcado.',
            'instagram_friend_2.regex' => 'Informe um usuario valido para o segundo amigo.',
            'instagram_friend_2.different' => 'Os usuarios marcados devem ser diferentes.',
            'instagram_requirements.accepted' => 'Confirme que voce cumpriu as regras no Instagram.',
            'rules.accepted' => 'Voce precisa aceitar o regulamento.',
            'password.required' => 'A senha e obrigatoria para criar sua conta.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmacao da senha nao confere.',
        ]);

        return $validator->validate();
    }

    private function generateAvailableRandomNumber(Sorteio $sorteio): int
    {
        $min = max(1, (int) $sorteio->numero_inicial);
        $max = $this->randomNumberMax($sorteio, $min);

        if ($min > $max) {
            throw ValidationException::withMessages([
                'sorteio' => 'Nao ha numeros disponiveis para este sorteio.',
            ]);
        }

        $usedNumbers = SorteioParticipante::where('sorteio_id', $sorteio->id)
            ->whereBetween('numero', [$min, $max])
            ->pluck('numero')
            ->map(fn ($numero) => (int) $numero)
            ->all();

        $availableCount = ($max - $min + 1) - count($usedNumbers);

        if ($availableCount <= 0) {
            throw ValidationException::withMessages([
                'sorteio' => 'Todos os numeros deste sorteio ja foram reservados.',
            ]);
        }

        $usedLookup = array_flip($usedNumbers);
        $rangeSize = $max - $min + 1;

        if ($rangeSize <= self::DEFAULT_RANDOM_NUMBER_POOL_SIZE) {
            $availableNumbers = [];

            for ($numero = $min; $numero <= $max; $numero++) {
                if (! isset($usedLookup[$numero])) {
                    $availableNumbers[] = $numero;
                }
            }

            return $availableNumbers[random_int(0, count($availableNumbers) - 1)];
        }

        for ($attempt = 0; $attempt < self::RANDOM_NUMBER_ATTEMPTS; $attempt++) {
            $numero = random_int($min, $max);

            if (! isset($usedLookup[$numero])) {
                return $numero;
            }
        }

        sort($usedNumbers);
        $candidate = $min;

        foreach ($usedNumbers as $usedNumber) {
            if ($usedNumber < $candidate) {
                continue;
            }

            if ($usedNumber > $candidate) {
                return $candidate;
            }

            $candidate++;
        }

        if ($candidate <= $max) {
            return $candidate;
        }

        throw ValidationException::withMessages([
            'sorteio' => 'Nao foi possivel reservar um numero para este sorteio.',
        ]);
    }

    private function randomNumberMax(Sorteio $sorteio, int $min): int
    {
        if ($sorteio->max_participantes !== null) {
            return $min + (int) $sorteio->max_participantes - 1;
        }

        return $min + self::DEFAULT_RANDOM_NUMBER_POOL_SIZE - 1;
    }

    private function normalizeInstagram(?string $value): string
    {
        return strtolower(ltrim(trim((string) $value), '@'));
    }

    private function digitsOnly(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    private function isValidCpf(string $cpf): bool
    {
        if (! preg_match('/^\d{11}$/', $cpf) || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($position = 9; $position < 11; $position++) {
            $sum = 0;

            for ($i = 0; $i < $position; $i++) {
                $sum += (int) $cpf[$i] * (($position + 1) - $i);
            }

            $digit = ((10 * $sum) % 11) % 10;

            if ((int) $cpf[$position] !== $digit) {
                return false;
            }
        }

        return true;
    }
}
