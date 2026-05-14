<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itens_pedido', function (Blueprint $table) {
            $table->string('status_preparacao', 30)->default('pendente')->after('custo_declarado_em');
            $table->timestamp('status_preparacao_em')->nullable()->after('status_preparacao');
        });
    }

    public function down(): void
    {
        Schema::table('itens_pedido', function (Blueprint $table) {
            $table->dropColumn(['status_preparacao', 'status_preparacao_em']);
        });
    }
};
