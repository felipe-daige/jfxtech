<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('itens_pedido', function (Blueprint $table) {
            $table->foreignId('produto_variante_id')
                  ->nullable()
                  ->after('produto_id')
                  ->constrained('produto_variantes')
                  ->nullOnDelete();
            $table->json('opcoes_snapshot')->nullable()->after('produto_variante_id');
        });
    }
    public function down(): void {
        Schema::table('itens_pedido', function (Blueprint $table) {
            $table->dropForeign(['produto_variante_id']);
            $table->dropColumn(['produto_variante_id', 'opcoes_snapshot']);
        });
    }
};
