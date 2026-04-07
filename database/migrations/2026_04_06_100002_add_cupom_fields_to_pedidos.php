<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->string('cupom_codigo', 50)->nullable()->after('frete_valor');
            $table->decimal('valor_desconto', 10, 2)->default(0)->after('cupom_codigo');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn(['cupom_codigo', 'valor_desconto']);
        });
    }
};
