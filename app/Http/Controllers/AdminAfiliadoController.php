<?php
namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\AffiliateSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAfiliadoController extends Controller
{
    private function checkAuth(): \Illuminate\Http\RedirectResponse|null
    {
        if (!Auth::check()) {
            return redirect()->route('site.login');
        }
        return null;
    }

    public function index()
    {
        if ($r = $this->checkAuth()) return $r;

        $afiliados = Affiliate::with('user')
            ->withCount('referrals')
            ->withCount(['referrals as convertidas_count' => fn($q) => $q->where('status', 'convertido')])
            ->orderByDesc('created_at')
            ->paginate(30);

        $metrics = [
            'ativos'              => Affiliate::where('status', 'ativo')->count(),
            'pendentes'           => Affiliate::where('status', 'pendente')->count(),
            'indicacoes_hoje'     => \App\Models\AffiliateReferral::whereDate('created_at', today())->count(),
            'comissoes_pendentes' => AffiliateCommission::where('status', 'pendente')->sum('valor'),
            'comissoes_pagas'     => AffiliateCommission::where('status', 'pago')->sum('valor'),
        ];

        return view('admin.afiliados.index', compact('afiliados', 'metrics'));
    }

    public function stream()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        return response()->stream(function () {
            set_time_limit(0);
            while (true) {
                $data = [
                    'afiliados_ativos'         => Affiliate::where('status', 'ativo')->count(),
                    'indicacoes_hoje'           => \App\Models\AffiliateReferral::whereDate('created_at', today())->count(),
                    'comissoes_pendentes_valor' => (float) AffiliateCommission::where('status', 'pendente')->sum('valor'),
                    'comissoes_pagas_valor'     => (float) AffiliateCommission::where('status', 'pago')->sum('valor'),
                ];
                echo 'data: ' . json_encode($data) . "\n\n";
                ob_flush();
                flush();
                if (connection_aborted()) break;
                sleep(30);
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function show(int $id)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $affiliate = Affiliate::with('user')->findOrFail($id);

        return response()->json([
            'id'               => $affiliate->id,
            'nome'             => $affiliate->user?->name ?? '',
            'email'            => $affiliate->user?->email ?? '',
            'codigo'           => $affiliate->codigo,
            'commission_type'  => $affiliate->commission_type,
            'commission_value' => $affiliate->commission_value,
            'status'           => $affiliate->status,
            'pix_key'          => $affiliate->pix_key,
            'bank_info'        => $affiliate->bank_info,
            'approved_at'      => $affiliate->approved_at?->format('d/m/Y'),
        ]);
    }

    public function aprovar(int $id)
    {
        if ($r = $this->checkAuth()) return $r;

        $affiliate = Affiliate::with('user')->findOrFail($id);
        $affiliate->update(['status' => 'ativo', 'approved_at' => now()]);

        $name = $affiliate->user?->name ?? "#" . $affiliate->id;
        return redirect()->route('admin.afiliados.index')
            ->with('success', "Afiliado {$name} aprovado.");
    }

    public function suspender(int $id)
    {
        if ($r = $this->checkAuth()) return $r;

        $affiliate = Affiliate::with('user')->findOrFail($id);
        $affiliate->update(['status' => 'inativo']);

        $name = $affiliate->user?->name ?? "#" . $affiliate->id;
        return redirect()->route('admin.afiliados.index')
            ->with('success', "Afiliado {$name} suspenso.");
    }

    public function editarComissao(Request $request, int $id)
    {
        if ($r = $this->checkAuth()) return $r;

        $affiliate = Affiliate::findOrFail($id);

        $request->validate([
            'commission_type'  => 'required|in:percent,fixed',
            'commission_value' => 'nullable|numeric|min:0|max:99999',
        ]);

        $affiliate->update([
            'commission_type'  => $request->commission_type,
            'commission_value' => $request->commission_value ?: null,
        ]);

        return redirect()->route('admin.afiliados.index')
            ->with('success', 'Comissão atualizada.');
    }

    public function comissoes(Request $request)
    {
        if ($r = $this->checkAuth()) return $r;

        $query = AffiliateCommission::with(['affiliate.user', 'pedido'])
            ->orderByDesc('created_at');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $comissoes = $query->paginate(30);

        $totais = [
            'pendente' => AffiliateCommission::where('status', 'pendente')->sum('valor'),
            'aprovado' => AffiliateCommission::where('status', 'aprovado')->sum('valor'),
            'pago'     => AffiliateCommission::where('status', 'pago')->sum('valor'),
        ];

        return view('admin.afiliados.comissoes', compact('comissoes', 'totais'));
    }

    public function bulkComissoes(Request $request)
    {
        if ($r = $this->checkAuth()) return $r;

        $request->validate([
            'ids'    => 'required|array',
            'ids.*'  => 'integer',
            'action' => 'required|in:aprovar,rejeitar,marcar_pago',
        ]);

        $commissions = AffiliateCommission::whereIn('id', $request->ids)->get();

        foreach ($commissions as $commission) {
            match ($request->action) {
                'aprovar'     => $commission->update(['status' => 'aprovado']),
                'rejeitar'    => $commission->update(['status' => 'rejeitado']),
                'marcar_pago' => $commission->update(['status' => 'pago', 'paid_at' => now()]),
            };
        }

        return redirect()->route('admin.afiliados.comissoes')
            ->with('success', count($commissions) . ' comissão(ões) atualizada(s).');
    }

    public function configuracoes()
    {
        if ($r = $this->checkAuth()) return $r;

        $settings = AffiliateSetting::whereIn('key', [
            'commission_percent_default',
            'cookie_days',
            'grace_period_days',
        ])->pluck('value', 'key');

        return view('admin.afiliados.configuracoes', compact('settings'));
    }

    public function salvarConfiguracoes(Request $request)
    {
        if ($r = $this->checkAuth()) return $r;

        $request->validate([
            'commission_percent_default' => 'required|numeric|min:0|max:100',
            'cookie_days'               => 'required|integer|min:1|max:365',
            'grace_period_days'         => 'required|integer|min:0|max:365',
        ]);

        foreach (['commission_percent_default', 'cookie_days', 'grace_period_days'] as $key) {
            AffiliateSetting::where('key', $key)
                ->update(['value' => $request->$key]);
        }

        return redirect()->route('admin.afiliados.configuracoes')
            ->with('success', 'Configurações salvas.');
    }
}
