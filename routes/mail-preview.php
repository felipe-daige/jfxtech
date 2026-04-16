<?php

/**
 * Mail preview routes — available only in local/testing environments.
 * Access: /mail-preview/{template}
 */

use App\Enums\PedidoStatus;
use App\Mail\OrderStatusMail;
use App\Models\Pedido;
use Illuminate\Support\Facades\Route;

if (!app()->environment('production')) {

    Route::get('/mail-preview/{template?}', function (string $template = null) {

        // Build a fake pedido for previews
        $pedido = new Pedido([
            'id' => 123,
            'status' => PedidoStatus::PAGO,
            'valor_total' => 549.90,
            'frete_tipo' => 'SEDEX',
            'frete_valor' => 29.90,
            'codigo_rastreio' => 'BR123456789BR',
            'customer_name' => 'João Silva',
            'customer_email' => 'joao@example.com',
            'customer_phone' => '43999999999',
            'checkout_mode' => 'authenticated',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Fake line items using anonymous objects
        $item1 = new \stdClass();
        $item1->quantidade = 1;
        $item1->subtotal = 449.90;
        $item1->produto = (object) ['nome' => 'Mouse Zowie EC2-CW'];

        $item2 = new \stdClass();
        $item2->quantidade = 2;
        $item2->subtotal = 100.00;
        $item2->produto = (object) ['nome' => 'Mousepad Artisan FX Zero'];

        $pedido->setRelation('itens', collect([$item1, $item2]));

        $orderUrl = url('/pedidos/123');

        $templates = [
            'received' => 'received',
            'paid' => PedidoStatus::PAGO,
            'processing' => PedidoStatus::PROCESSANDO,
            'shipped' => PedidoStatus::ENVIADO,
            'delivered' => PedidoStatus::ENTREGUE,
            'reset-password' => 'reset-password',
        ];

        // Index page
        if (!$template) {
            $links = '';
            foreach (array_keys($templates) as $key) {
                $links .= "<a href='/mail-preview/{$key}' style='display:block;padding:12px 20px;margin:8px 0;background:#111;color:#fff;text-decoration:none;border-radius:6px;font-family:sans-serif;font-size:14px;letter-spacing:0.04em;'>{$key}</a>";
            }

            return "<div style='max-width:400px;margin:60px auto;font-family:sans-serif;'>"
                . "<h1 style='font-size:20px;margin-bottom:24px;'>📧 Mail Preview</h1>"
                . $links
                . "</div>";
        }

        // Reset password
        if ($template === 'reset-password') {
            return view('emails.auth.reset-password', [
                'url' => url('/reset-password/fake-token?email=joao@example.com'),
                'title' => 'Redefinição de senha',
            ]);
        }

        // Order emails
        $eventType = $templates[$template] ?? PedidoStatus::PAGO;

        // Adjust pedido status to match the event
        $statusMap = [
            'received' => PedidoStatus::PENDENTE,
            'paid' => PedidoStatus::PAGO,
            'processing' => PedidoStatus::PROCESSANDO,
            'shipped' => PedidoStatus::ENVIADO,
            'delivered' => PedidoStatus::ENTREGUE,
        ];
        $pedido->status = $statusMap[$template] ?? $pedido->status;

        $mail = new OrderStatusMail($pedido, $eventType, $orderUrl);

        return $mail->render();
    })->name('mail-preview');
}
