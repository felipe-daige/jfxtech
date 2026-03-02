<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->string('marca', 100)->nullable()->after('nome');
            $table->text('descricao_curta')->nullable()->after('descricao');
            $table->json('specs')->nullable()->after('descricao_curta');
        });
    }

    public function down(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->dropColumn(['marca', 'descricao_curta', 'specs']);
        });
    }
};
