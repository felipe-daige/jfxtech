<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('slug')->unique();
            $table->text('descricao');
            $table->decimal('preco', 10, 2);
            $table->decimal('preco_original', 10, 2)->nullable();
            $table->decimal('desconto_percentual', 5, 2)->nullable();
            $table->boolean('em_promocao')->default(false);
            $table->boolean('destaque')->default(false);
            $table->decimal('peso', 8, 3)->nullable()->comment('Peso do produto em KG');
            $table->integer('estoque')->default(0);
            $table->boolean('ativo')->default(true);
            $table->foreignId('categoria_id')->constrained('categorias')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
