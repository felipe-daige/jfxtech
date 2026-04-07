<?php

namespace Tests\Unit;

use App\Enums\PedidoStatus;
use PHPUnit\Framework\TestCase;

class PedidoStatusTest extends TestCase
{
    public function test_admin_values_are_expected(): void
    {
        $expected = [
            PedidoStatus::PENDENTE,
            PedidoStatus::PAGO,
            PedidoStatus::PROCESSANDO,
            PedidoStatus::ENVIADO,
            PedidoStatus::ENTREGUE,
            PedidoStatus::CANCELADO,
        ];

        $this->assertSame($expected, PedidoStatus::adminValues());
    }

    public function test_labels_include_friendly_names(): void
    {
        $this->assertSame('Aguardando confirmação', PedidoStatus::label(PedidoStatus::PENDENTE));
        $this->assertSame('Pagamento confirmado', PedidoStatus::label(PedidoStatus::PAGO));
        $this->assertSame('Em preparação', PedidoStatus::label(PedidoStatus::PROCESSANDO));
        $this->assertSame('Enviado para entrega', PedidoStatus::label(PedidoStatus::ENVIADO));
    }

    public function test_badge_classes_cover_every_status(): void
    {
        foreach (PedidoStatus::values() as $status) {
            $this->assertIsString(PedidoStatus::badgeClass($status));
        }
    }
}
