<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Configuracao extends Model
{
    protected $table = 'configuracoes';

    protected $fillable = ['chave', 'valor'];

    public static function get(string $chave, mixed $default = null): mixed
    {
        return Cache::remember("configuracao_{$chave}", 60, function () use ($chave, $default) {
            $config = static::where('chave', $chave)->first();
            return $config ? $config->valor : $default;
        });
    }

    public static function set(string $chave, mixed $valor): void
    {
        static::updateOrCreate(['chave' => $chave], ['valor' => (string) $valor]);
        Cache::forget("configuracao_{$chave}");
    }
}
