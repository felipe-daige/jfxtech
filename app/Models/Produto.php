<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Produto extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'slug',
        'marca',
        'descricao',
        'descricao_curta',
        'specs',
        'preco',
        'custo_compra',
        'peso',
        'preco_original',
        'desconto_percentual',
        'em_promocao',
        'destaque',
        'estoque',
        'estoque_compartilhado',
        'categoria_id',
        'ativo',
        'tags'
    ];

    protected $casts = [
        'preco' => 'decimal:2',
        'custo_compra' => 'decimal:2',
        'preco_original' => 'decimal:2',
        'desconto_percentual' => 'decimal:2',
        'em_promocao' => 'boolean',
        'destaque' => 'boolean',
        'estoque' => 'integer',
        'estoque_compartilhado' => 'boolean',
        'ativo' => 'boolean',
        'tags' => 'array',
        'specs' => 'array',
    ];

    /**
     * Boot method para gerar slug automaticamente
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($produto) {
            // Sempre gerar slug único ao criar
            $produto->slug = static::generateUniqueSlug($produto->nome);
        });
        
        static::updating(function ($produto) {
            // Gerar novo slug se o nome mudou
            if ($produto->isDirty('nome')) {
                $produto->slug = static::generateUniqueSlug($produto->nome, $produto->id);
            }
        });
    }

    /**
     * Gerar slug único baseado no nome
     */
    protected static function generateUniqueSlug($nome, $excludeId = null)
    {
        $baseSlug = Str::slug($nome);
        $slug = $baseSlug;
        $counter = 1;
        
        // Garantir que o slug seja único
        while (static::where('slug', $slug)->when($excludeId, function($query) use ($excludeId) {
            return $query->where('id', '!=', $excludeId);
        })->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Relacionamento: Um produto pertence a uma categoria
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * Relacionamento: Um produto pode ter várias imagens
     */
    public function imagens(): HasMany
    {
        return $this->hasMany(ProdutoImagem::class)->orderBy('ordem')->orderBy('id');
    }

    /**
     * Relacionamento: Um produto pode estar em vários itens de pedido
     */
    public function itensPedido(): HasMany
    {
        return $this->hasMany(ItemPedido::class);
    }

    /**
     * Relacionamento: Um produto pode ser favoritado por vários usuários
     */
    public function favoritos(): HasMany
    {
        return $this->hasMany(Favorito::class);
    }

    /**
     * Relacionamento: Um produto pode estar em vários pedidos através dos itens
     */
    public function pedidos(): BelongsToMany
    {
        return $this->belongsToMany(Pedido::class, 'itens_pedido')
                    ->withPivot('quantidade', 'preco')
                    ->withTimestamps();
    }

    /**
     * Accessor para verificar se o produto está em estoque
     */
    public function getEmEstoqueAttribute(): bool
    {
        return $this->estoque > 0;
    }

    /**
     * Accessor para obter a primeira imagem do produto
     */
    public function getPrimeiraImagemAttribute(): ?string
    {
        $imagem = $this->imagens()->first();
        return $imagem ? $imagem->caminho : null;
    }

    /**
     * Relacionamento: Uma imagem de capa do produto
     */
    public function imagemCapa()
    {
        return $this->hasOne(ProdutoImagem::class)->where('capa', true);
    }

    /**
     * Accessor para obter o preço com desconto aplicado
     */
    public function getPrecoComDescontoAttribute(): float
    {
        if ($this->em_promocao && $this->desconto_percentual > 0) {
            return $this->preco_original * (1 - $this->desconto_percentual / 100);
        }
        return $this->preco;
    }

    public function getLucroBrutoUnitarioAttribute(): ?float
    {
        if ($this->custo_compra === null) {
            return null;
        }

        return round($this->preco_com_desconto - (float) $this->custo_compra, 2);
    }

    public function getMargemBrutaPercentualAttribute(): ?float
    {
        $precoEfetivo = $this->preco_com_desconto;
        if ($this->custo_compra === null || $precoEfetivo <= 0) {
            return null;
        }

        return round((($precoEfetivo - (float) $this->custo_compra) / $precoEfetivo) * 100, 2);
    }

    /**
     * Accessor para obter o valor do desconto em reais
     */
    public function getValorDescontoAttribute(): float
    {
        if ($this->em_promocao && $this->desconto_percentual > 0) {
            return $this->preco_original - $this->preco;
        }
        return 0;
    }

    /**
     * Accessor para verificar se o produto está em promoção
     */
    public function getEstaEmPromocaoAttribute(): bool
    {
        return $this->em_promocao && $this->desconto_percentual > 0;
    }

    public function opcaoGrupos(): HasMany
    {
        return $this->hasMany(ProdutoOpcaoGrupo::class)->orderBy('ordem')->orderBy('id');
    }

    public function variantes(): HasMany
    {
        return $this->hasMany(ProdutoVariante::class);
    }

    public function variantesAtivas(): HasMany
    {
        return $this->hasMany(ProdutoVariante::class)->where('ativo', true);
    }

    public function getTemVariantesAttribute(): bool
    {
        if ($this->relationLoaded('opcaoGrupos')) {
            return $this->opcaoGrupos->isNotEmpty();
        }
        return $this->opcaoGrupos()->exists();
    }
}
