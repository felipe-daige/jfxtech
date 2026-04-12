<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produto_variantes', function (Blueprint $table) {
            $table->text('descricao')->nullable();
            $table->json('specs')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('produto_variantes', function (Blueprint $table) {
            $table->dropColumn(['descricao', 'specs']);
        });
    }
};
