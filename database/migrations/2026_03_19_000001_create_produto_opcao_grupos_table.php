<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('produto_opcao_grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained('produtos')->onDelete('cascade');
            $table->string('nome');
            $table->integer('ordem')->default(0);
            $table->timestamps();
            $table->unique(['produto_id', 'nome']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('produto_opcao_grupos');
    }
};
