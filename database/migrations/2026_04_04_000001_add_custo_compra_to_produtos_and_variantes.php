<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->decimal('custo_compra', 10, 2)->nullable()->after('preco');
        });

        Schema::table('produto_variantes', function (Blueprint $table) {
            $table->decimal('custo_compra', 10, 2)->nullable()->after('preco');
        });
    }

    public function down(): void
    {
        Schema::table('produto_variantes', function (Blueprint $table) {
            $table->dropColumn('custo_compra');
        });

        Schema::table('produtos', function (Blueprint $table) {
            $table->dropColumn('custo_compra');
        });
    }
};
