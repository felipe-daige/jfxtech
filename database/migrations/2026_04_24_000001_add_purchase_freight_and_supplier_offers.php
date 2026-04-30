<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->decimal('frete_compra', 10, 2)->nullable()->after('custo_compra');
        });

        Schema::table('produto_variantes', function (Blueprint $table) {
            $table->decimal('frete_compra', 10, 2)->nullable()->after('custo_compra');
        });

        Schema::create('fornecedores', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->nullable();
            $table->string('perfil_url', 2048)->nullable();
            $table->string('site_url', 2048)->nullable();
            $table->string('email')->nullable();
            $table->string('telefone', 50)->nullable();
            $table->string('whatsapp', 50)->nullable();
            $table->string('contato_nome')->nullable();
            $table->string('pais', 80)->nullable();
            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->nullable()->default(true);
            $table->timestamps();

            $table->index('nome');
            $table->index('ativo');
        });

        Schema::create('produto_fornecedor_ofertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained('produtos')->cascadeOnDelete();
            $table->foreignId('fornecedor_id')->constrained('fornecedores')->cascadeOnDelete();
            $table->decimal('preco_compra', 10, 2)->nullable();
            $table->decimal('frete_compra', 10, 2)->nullable();
            $table->string('moeda', 10)->nullable();
            $table->unsignedInteger('quantidade_minima')->nullable();
            $table->unsignedInteger('prazo_dias')->nullable();
            $table->string('url_produto', 2048)->nullable();
            $table->string('sku_fornecedor')->nullable();
            $table->text('observacoes')->nullable();
            $table->date('cotado_em')->nullable();
            $table->boolean('ativo')->nullable()->default(true);
            $table->timestamps();

            $table->unique(['produto_id', 'fornecedor_id'], 'produto_fornecedor_unique');
            $table->index('ativo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produto_fornecedor_ofertas');
        Schema::dropIfExists('fornecedores');

        Schema::table('produto_variantes', function (Blueprint $table) {
            $table->dropColumn('frete_compra');
        });

        Schema::table('produtos', function (Blueprint $table) {
            $table->dropColumn('frete_compra');
        });
    }
};
