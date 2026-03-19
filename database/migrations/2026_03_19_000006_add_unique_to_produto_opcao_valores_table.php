<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produto_opcao_valores', function (Blueprint $table) {
            $table->unique(['grupo_id', 'valor']);
        });
    }

    public function down(): void
    {
        Schema::table('produto_opcao_valores', function (Blueprint $table) {
            $table->dropUnique(['grupo_id', 'valor']);
        });
    }
};
