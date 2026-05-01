<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sorteios', function (Blueprint $table) {
            $table->foreignId('produto_id')
                ->nullable()
                ->after('premio')
                ->constrained('produtos')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sorteios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('produto_id');
        });
    }
};
