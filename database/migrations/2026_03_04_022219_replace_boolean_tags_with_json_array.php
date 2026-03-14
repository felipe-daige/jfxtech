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
        Schema::table('produtos', function (Blueprint $table) {
            // Remove as colunas antigas booleanas
            if (Schema::hasColumn('produtos', 'exclusivo')) {
                $table->dropColumn('exclusivo');
            }
            if (Schema::hasColumn('produtos', 'em_breve')) {
                $table->dropColumn('em_breve');
            }

            // Adiciona a nova coluna JSON para as tags
            $table->json('tags')->nullable()->after('destaque');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->dropColumn('tags');
            
            $table->boolean('exclusivo')->default(false)->after('destaque');
            $table->boolean('em_breve')->default(false)->after('exclusivo');
        });
    }
};
