<?php
namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Services\AffiliateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AffiliadoController extends Controller
{
    public function __construct(protected AffiliateService $affiliateService) {}

    public function painel()
    {
        $affiliate = Affiliate::where('user_id', Auth::id())->first();

        if (!$affiliate) {
            return redirect()->route('afiliados.solicitar');
        }

        if ($affiliate->status === 'pendente') {
            return view('site.afiliados.painel', compact('affiliate'));
        }

        $stats = [
            'total_indicacoes'    => $affiliate->referrals()->count(),
            'convertidas'         => $affiliate->referrals()->where('status', 'convertido')->count(),
            'comissoes_pendentes' => $affiliate->commissions()->where('status', 'pendente')->sum('valor'),
            'comissoes_pagas'     => $affiliate->commissions()->where('status', 'pago')->sum('valor'),
        ];

        $ultimasIndicacoes = $affiliate->referrals()->with('referredUser')->latest()->limit(5)->get();
        $ultimasComissoes  = $affiliate->commissions()->with('pedido')->latest()->limit(5)->get();
        $linkIndicacao     = url('/') . '/?ref=' . $affiliate->codigo;

        return view('site.afiliados.painel', compact(
            'affiliate', 'stats', 'ultimasIndicacoes', 'ultimasComissoes', 'linkIndicacao'
        ));
    }

    public function solicitar()
    {
        if (Affiliate::where('user_id', Auth::id())->exists()) {
            return redirect()->route('afiliados.painel');
        }
        return view('site.afiliados.solicitar');
    }

    public function registrar(Request $request)
    {
        if (Affiliate::where('user_id', Auth::id())->exists()) {
            return redirect()->route('afiliados.painel');
        }

        $request->validate([
            'pix_key'   => 'nullable|string|max:255',
            'bank_info' => 'nullable|string|max:1000',
        ]);

        Affiliate::create([
            'user_id'          => Auth::id(),
            'codigo'           => $this->affiliateService->generateUniqueCode(),
            'commission_type'  => 'percent',
            'commission_value' => null,
            'status'           => 'pendente',
            'pix_key'          => $request->pix_key,
            'bank_info'        => $request->bank_info,
        ]);

        return redirect()->route('afiliados.painel')
            ->with('success', 'Solicitação enviada! Aguarde aprovação do administrador.');
    }

    public function indicacoes()
    {
        $affiliate = Affiliate::where('user_id', Auth::id())->firstOrFail();
        $indicacoes = $affiliate->referrals()->with('referredUser')->latest()->paginate(20);
        return view('site.afiliados.indicacoes', compact('affiliate', 'indicacoes'));
    }

    public function comissoes()
    {
        $affiliate = Affiliate::where('user_id', Auth::id())->firstOrFail();
        $comissoes = $affiliate->commissions()->with('pedido')->latest()->paginate(20);
        $totais = [
            'pendente' => $affiliate->commissions()->where('status', 'pendente')->sum('valor'),
            'aprovado' => $affiliate->commissions()->where('status', 'aprovado')->sum('valor'),
            'pago'     => $affiliate->commissions()->where('status', 'pago')->sum('valor'),
        ];
        return view('site.afiliados.comissoes', compact('affiliate', 'comissoes', 'totais'));
    }
}
