<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sorteio extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'slug',
        'premio',
        'produto_id',
        'descricao',
        'instagram_post_url',
        'starts_at',
        'ends_at',
        'ativo',
        'numero_inicial',
        'max_participantes',
        'ganhador_participante_id',
        'resultado_publicado_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'ativo' => 'boolean',
            'numero_inicial' => 'integer',
            'max_participantes' => 'integer',
            'resultado_publicado_at' => 'datetime',
        ];
    }

    public function participantes(): HasMany
    {
        return $this->hasMany(SorteioParticipante::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function ganhador(): BelongsTo
    {
        return $this->belongsTo(SorteioParticipante::class, 'ganhador_participante_id');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function inscricoesAbertas(): bool
    {
        if (! $this->ativo || $this->resultado_publicado_at) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        if ($this->max_participantes !== null && $this->participantes()->count() >= $this->max_participantes) {
            return false;
        }

        return true;
    }

    public function resultadoPublicado(): bool
    {
        return $this->resultado_publicado_at !== null && $this->ganhador_participante_id !== null;
    }
}
