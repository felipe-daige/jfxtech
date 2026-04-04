<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produto_variante_imagens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variante_id')->constrained('produto_variantes')->onDelete('cascade');
            $table->foreignId('imagem_id')->constrained('produto_imagens')->onDelete('cascade');
            $table->unique(['variante_id', 'imagem_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produto_variante_imagens');
    }
};
