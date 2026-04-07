<?php

namespace App\Services;

use App\Enums\PedidoStatus;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CheckoutOrderService
{
    public const SESSION_ORDER_ID = 'checkout_order_id';
    public const SESSION_ORDER_TOKEN = 'checkout_order_token';

    public function resolveActiveOrder(Request $request, array $with = [], array $statuses = [PedidoStatus::CARRINHO, PedidoStatus::PENDENTE, PedidoStatus::PROCESSANDO]): ?Pedido
    {
        if (Auth::check()) {
            $query = Pedido::query()
                ->where('user_id', Auth::id());

            return $this->resolveLatestByStatuses($query, $with, $statuses);
        }

        $pedidoId = $request->session()->get(self::SESSION_ORDER_ID);
        if (!$pedidoId) {
            return null;
        }

        return Pedido::query()
            ->when($with !== [], fn ($builder) => $builder->with($with))
            ->where('id', $pedidoId)
            ->whereNull('user_id')
            ->whereIn('status', $statuses)
            ->first();
    }

    public function ensureCart(Request $request, array $with = []): Pedido
    {
        if (Auth::check()) {
            $cart = Pedido::query()
                ->when($with !== [], fn ($builder) => $builder->with($with))
                ->where('user_id', Auth::id())
                ->where('status', PedidoStatus::CARRINHO)
                ->latest('id')
                ->first();

            if ($cart) {
                return $cart;
            }

            return Pedido::create([
                'user_id' => Auth::id(),
                'status' => PedidoStatus::CARRINHO,
                'valor_total' => 0,
                'checkout_mode' => 'authenticated',
            ]);
        }

        $cart = $this->resolveActiveOrder($request, $with, [PedidoStatus::CARRINHO]);
        if ($cart) {
            return $cart;
        }

        $cart = Pedido::create([
            'status' => PedidoStatus::CARRINHO,
            'valor_total' => 0,
            'checkout_mode' => 'guest',
            'guest_token' => (string) Str::uuid(),
        ]);

        $this->rememberGuestOrder($request, $cart);

        return $cart;
    }

    public function rememberGuestOrder(Request $request, Pedido $pedido): void
    {
        if ($pedido->user_id !== null) {
            return;
        }

        if (!$pedido->guest_token) {
            $pedido->forceFill([
                'guest_token' => (string) Str::uuid(),
            ])->save();
        }

        $request->session()->put(self::SESSION_ORDER_ID, $pedido->id);
        $request->session()->put(self::SESSION_ORDER_TOKEN, $pedido->guest_token);
    }

    public function clearGuestOrder(Request $request, ?Pedido $pedido = null): void
    {
        if ($pedido === null || $pedido->user_id === null) {
            $request->session()->forget([
                self::SESSION_ORDER_ID,
                self::SESSION_ORDER_TOKEN,
            ]);
        }
    }

    public function canAccessOrder(Request $request, Pedido $pedido): bool
    {
        if (Auth::check() && (int) $pedido->user_id === (int) Auth::id()) {
            return true;
        }

        if ($pedido->user_id !== null || !$pedido->guest_token) {
            return false;
        }

        $sessionOrderId = $request->session()->get(self::SESSION_ORDER_ID);
        $sessionToken = (string) $request->session()->get(self::SESSION_ORDER_TOKEN, '');
        $requestToken = (string) ($request->query('token') ?? $request->input('guest_token') ?? '');

        if ((int) $sessionOrderId === (int) $pedido->id && $sessionToken !== '' && hash_equals($pedido->guest_token, $sessionToken)) {
            return true;
        }

        return $requestToken !== '' && hash_equals($pedido->guest_token, $requestToken);
    }

    public function orderUrl(Pedido $pedido): string
    {
        $url = route('site.pedidos.show', $pedido);

        if ($pedido->user_id === null && $pedido->guest_token) {
            $url .= '?token=' . urlencode($pedido->guest_token);
        }

        return $url;
    }

    protected function resolveLatestByStatuses($query, array $with, array $statuses): ?Pedido
    {
        $cart = (clone $query)
            ->when($with !== [], fn ($builder) => $builder->with($with))
            ->where('status', PedidoStatus::CARRINHO)
            ->latest('id')
            ->first();

        if ($cart) {
            return $cart;
        }

        if (!in_array(PedidoStatus::PENDENTE, $statuses, true) && !in_array(PedidoStatus::PROCESSANDO, $statuses, true)) {
            return null;
        }

        return (clone $query)
            ->when($with !== [], fn ($builder) => $builder->with($with))
            ->whereIn('status', array_values(array_intersect($statuses, [PedidoStatus::PENDENTE, PedidoStatus::PROCESSANDO])))
            ->latest('id')
            ->first();
    }
}
