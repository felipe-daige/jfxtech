<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SorteioParticipante extends Model
{
    use HasFactory;

    public const STATUS_PENDENTE = 'pendente';

    public const STATUS_VALIDADO = 'validado';

    public const STATUS_DESCLASSIFICADO = 'desclassificado';

    public const STATUS_LABELS = [
        self::STATUS_PENDENTE => 'Pendente de auditoria',
        self::STATUS_VALIDADO => 'Validado',
        self::STATUS_DESCLASSIFICADO => 'Desclassificado',
    ];

    protected $fillable = [
        'sorteio_id',
        'user_id',
        'numero',
        'instagram_username',
        'instagram_friend_1',
        'instagram_friend_2',
        'status',
        'accepted_rules_at',
        'instagram_requirements_accepted_at',
        'marketing_opt_in_at',
        'ip_address',
        'user_agent',
        'audited_at',
        'audit_notes',
    ];

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'accepted_rules_at' => 'datetime',
            'instagram_requirements_accepted_at' => 'datetime',
            'marketing_opt_in_at' => 'datetime',
            'audited_at' => 'datetime',
        ];
    }

    public function sorteio(): BelongsTo
    {
        return $this->belongsTo(Sorteio::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function numeroFormatado(): string
    {
        return str_pad((string) $this->numero, 5, '0', STR_PAD_LEFT);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function isGanhador(): bool
    {
        return (int) $this->sorteio?->ganhador_participante_id === (int) $this->id;
    }
}
