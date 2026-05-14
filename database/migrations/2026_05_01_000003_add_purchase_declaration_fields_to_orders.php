<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->string('nota_fiscal_imagem_path')->nullable()->after('codigo_rastreio');
        });

        Schema::table('itens_pedido', function (Blueprint $table) {
            $table->decimal('custo_unitario_declarado', 10, 2)->nullable()->after('preco');
            $table->timestamp('custo_declarado_em')->nullable()->after('custo_unitario_declarado');
        });
    }

    public function down(): void
    {
        Schema::table('itens_pedido', function (Blueprint $table) {
            $table->dropColumn(['custo_unitario_declarado', 'custo_declarado_em']);
        });

        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn('nota_fiscal_imagem_path');
        });
    }
};
