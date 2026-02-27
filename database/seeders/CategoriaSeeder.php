<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Categoria;

class CategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = [
            [
                'nome' => 'Capacetes',
                'slug' => 'capacetes',
                'ativo' => true
            ],
            [
                'nome' => 'Proteção',
                'slug' => 'protecao',
                'ativo' => true
            ],
            [
                'nome' => 'Calçados',
                'slug' => 'calcados',
                'ativo' => true
            ],
            [
                'nome' => 'Acessórios',
                'slug' => 'acessorios',
                'ativo' => true
            ],
            [
                'nome' => 'Roupas',
                'slug' => 'roupas',
                'ativo' => true
            ]
        ];

        foreach ($categorias as $categoria) {
            Categoria::create($categoria);
        }
    }
}
