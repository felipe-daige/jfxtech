<?php

namespace App\Enums;

final class PedidoStatus
{
    public const CARRINHO = 'carrinho';
    public const PENDENTE = 'pendente';
    public const PROCESSANDO = 'processando';
    public const PAGO = 'pago';
    public const ENVIADO = 'enviado';
    public const ENTREGUE = 'entregue';
    public const CANCELADO = 'cancelado';

    private const LABELS = [
        self::CARRINHO => 'Carrinho',
        self::PENDENTE => 'Aguardando confirmação',
        self::PROCESSANDO => 'Em preparação',
        self::PAGO => 'Pagamento confirmado',
        self::ENVIADO => 'Enviado para entrega',
        self::ENTREGUE => 'Entregue',
        self::CANCELADO => 'Cancelado',
    ];

    private const ADMIN_STATUS = [
        self::PENDENTE,
        self::PAGO,
        self::PROCESSANDO,
        self::ENVIADO,
        self::ENTREGUE,
        self::CANCELADO,
    ];

    private const PERFORMANCE_STATUS = [
        self::PAGO,
        self::PROCESSANDO,
        self::ENVIADO,
        self::ENTREGUE,
        self::CANCELADO,
    ];

    private const ACTIONABLE_STATUS = [
        self::PAGO,
        self::PROCESSANDO,
    ];

    private const NOTIFICATION_STATUS = [
        self::PAGO,
        self::PROCESSANDO,
        self::ENVIADO,
        self::ENTREGUE,
        self::CANCELADO,
    ];

    private const WEBHOOK_NOTIFICATION_STATUS = [
        self::PAGO,
        self::PROCESSANDO,
        self::ENVIADO,
        self::ENTREGUE,
    ];

    private const BADGE_CLASSES = [
        self::CARRINHO => 'border-gray-400 text-gray-600',
        self::PENDENTE => 'border-yellow-500 text-yellow-700',
        self::PROCESSANDO => 'border-blue-500 text-blue-700',
        self::PAGO => 'border-green-500 text-green-700',
        self::ENVIADO => 'border-purple-500 text-purple-700',
        self::ENTREGUE => 'border-green-700 text-green-900',
        self::CANCELADO => 'border-red-400 text-red-600',
    ];

    public static function values(): array
    {
        return array_keys(self::LABELS);
    }

    public static function adminValues(): array
    {
        return self::ADMIN_STATUS;
    }

    public static function performanceValues(): array
    {
        return self::PERFORMANCE_STATUS;
    }

    public static function actionableValues(): array
    {
        return self::ACTIONABLE_STATUS;
    }

    public static function notificationValues(): array
    {
        return self::NOTIFICATION_STATUS;
    }

    public static function webhookNotificationValues(): array
    {
        return self::WEBHOOK_NOTIFICATION_STATUS;
    }

    public static function labels(): array
    {
        return self::LABELS;
    }

    public static function label(string $status): string
    {
        return self::LABELS[$status] ?? ucfirst($status);
    }

    public static function badgeClasses(): array
    {
        return self::BADGE_CLASSES;
    }

    public static function badgeClass(string $status): string
    {
        return self::BADGE_CLASSES[$status] ?? 'border-gray-300 text-gray-500';
    }
}
