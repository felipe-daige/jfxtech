<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('produto_opcao_valores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('produto_opcao_grupos')->onDelete('cascade');
            $table->string('valor');
            $table->integer('ordem')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('produto_opcao_valores');
    }
};
