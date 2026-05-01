<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use App\Models\Sorteio;
use App\Models\SorteioParticipante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminSorteioController extends Controller
{
    public function index()
    {
        $sorteios = Sorteio::with(['ganhador.user', 'produto.imagens'])
            ->withCount('participantes')
            ->latest('id')
            ->paginate(12);
        $produtos = $this->produtosParaSelecao();

        return view('admin.sorteios.index', compact('sorteios', 'produtos'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateSorteio($request);
        $validated['slug'] = $this->uniqueSlug($validated['slug'] ?: $validated['titulo']);
        $validated['ativo'] = $request->boolean('ativo');

        Sorteio::create($validated);

        return redirect()
            ->route('admin.sorteios.index')
            ->with('success', 'Sorteio criado com sucesso.');
    }

    public function show(Request $request, Sorteio $sorteio)
    {
        $participantesQuery = $sorteio->participantes()
            ->with('user')
            ->latest('id');

        if ($request->filled('status')) {
            $participantesQuery->where('status', (string) $request->string('status'));
        }

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));

            $participantesQuery->where(function ($query) use ($search) {
                $query->where('numero', $search)
                    ->orWhere('instagram_username', 'like', "%{$search}%")
                    ->orWhere('instagram_friend_1', 'like', "%{$search}%")
                    ->orWhere('instagram_friend_2', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('cpf', 'like', "%{$search}%");
                    });
            });
        }

        $participantes = $participantesQuery->paginate(50)->withQueryString();

        $statusCounts = $sorteio->participantes()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $participantesParaResultado = $sorteio->participantes()
            ->with('user')
            ->where('status', '<>', SorteioParticipante::STATUS_DESCLASSIFICADO)
            ->orderBy('numero')
            ->get();

        return view('admin.sorteios.show', [
            'sorteio' => $sorteio->load(['ganhador.user', 'produto.imagens']),
            'participantes' => $participantes,
            'participantesParaResultado' => $participantesParaResultado,
            'produtos' => $this->produtosParaSelecao(),
            'statusCounts' => $statusCounts,
            'statusLabels' => SorteioParticipante::STATUS_LABELS,
        ]);
    }

    public function update(Request $request, Sorteio $sorteio)
    {
        $validated = $this->validateSorteio($request, $sorteio);
        $validated['slug'] = $this->uniqueSlug($validated['slug'] ?: $validated['titulo'], $sorteio->id);
        $validated['ativo'] = $request->boolean('ativo');

        $sorteio->update($validated);

        return back()->with('success', 'Sorteio atualizado com sucesso.');
    }

    public function destroy(Sorteio $sorteio)
    {
        $sorteio->update([
            'ganhador_participante_id' => null,
            'resultado_publicado_at' => null,
        ]);
        $sorteio->delete();

        return redirect()
            ->route('admin.sorteios.index')
            ->with('success', 'Sorteio removido com sucesso.');
    }

    public function updateParticipante(Request $request, Sorteio $sorteio, SorteioParticipante $participante)
    {
        abort_unless((int) $participante->sorteio_id === (int) $sorteio->id, 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(SorteioParticipante::STATUS_LABELS))],
            'audit_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $participante->update([
            'status' => $validated['status'],
            'audit_notes' => $validated['audit_notes'] ?? null,
            'audited_at' => $validated['status'] === SorteioParticipante::STATUS_PENDENTE ? null : now(),
        ]);

        if (
            $validated['status'] === SorteioParticipante::STATUS_DESCLASSIFICADO
            && (int) $sorteio->ganhador_participante_id === (int) $participante->id
        ) {
            $sorteio->update([
                'ganhador_participante_id' => null,
                'resultado_publicado_at' => null,
            ]);
        }

        return back()->with('success', 'Participante atualizado com sucesso.');
    }

    public function sortearCandidato(Sorteio $sorteio)
    {
        $participante = $sorteio->participantes()
            ->where('status', '<>', SorteioParticipante::STATUS_DESCLASSIFICADO)
            ->inRandomOrder()
            ->first();

        if (! $participante) {
            return back()->with('error', 'Nao ha participantes aptos para sortear.');
        }

        $sorteio->update([
            'ganhador_participante_id' => $participante->id,
            'resultado_publicado_at' => null,
        ]);

        return back()->with('success', 'Candidato sorteado: numero '.$participante->numeroFormatado().'. Audite e publique o resultado quando estiver confirmado.');
    }

    public function publicarResultado(Request $request, Sorteio $sorteio)
    {
        $validated = $request->validate([
            'ganhador_participante_id' => ['required', 'integer'],
        ]);

        $participante = $sorteio->participantes()
            ->whereKey($validated['ganhador_participante_id'])
            ->firstOrFail();

        if ($participante->status === SorteioParticipante::STATUS_DESCLASSIFICADO) {
            return back()->with('error', 'Nao e possivel publicar um participante desclassificado como ganhador.');
        }

        $participante->update([
            'status' => SorteioParticipante::STATUS_VALIDADO,
            'audited_at' => $participante->audited_at ?: now(),
        ]);

        $sorteio->update([
            'ganhador_participante_id' => $participante->id,
            'resultado_publicado_at' => now(),
        ]);

        return back()->with('success', 'Resultado publicado com sucesso.');
    }

    public function limparResultado(Sorteio $sorteio)
    {
        $sorteio->update([
            'ganhador_participante_id' => null,
            'resultado_publicado_at' => null,
        ]);

        return back()->with('success', 'Resultado removido com sucesso.');
    }

    private function validateSorteio(Request $request, ?Sorteio $sorteio = null): array
    {
        return $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'premio' => ['nullable', 'string', 'max:255'],
            'produto_id' => ['nullable', 'integer', 'exists:produtos,id'],
            'descricao' => ['nullable', 'string', 'max:5000'],
            'instagram_post_url' => ['nullable', 'url', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'numero_inicial' => ['required', 'integer', 'min:1'],
            'max_participantes' => ['nullable', 'integer', 'min:1'],
        ], [
            'titulo.required' => 'Informe o titulo do sorteio.',
            'instagram_post_url.url' => 'Informe uma URL valida para o post do Instagram.',
            'ends_at.after_or_equal' => 'A data de encerramento deve ser posterior ao inicio.',
            'numero_inicial.required' => 'Informe o numero inicial.',
        ]);
    }

    private function produtosParaSelecao()
    {
        return Produto::query()
            ->where('ativo', true)
            ->with('imagens')
            ->orderBy('nome')
            ->get(['id', 'nome', 'slug', 'preco', 'estoque', 'ativo']);
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'sorteio';
        $slug = $base;
        $suffix = 2;

        while (
            Sorteio::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
