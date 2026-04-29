<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cupons', function (Blueprint $table) {
            $table->decimal('comissao_percentual', 5, 2)->default(100)->after('ativo');
        });
    }

    public function down(): void
    {
        Schema::table('cupons', function (Blueprint $table) {
            $table->dropColumn('comissao_percentual');
        });
    }
};
