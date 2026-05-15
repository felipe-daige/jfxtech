<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->decimal('comprimento', 8, 2)->nullable()->after('peso'); // em cm
            $table->decimal('largura', 8, 2)->nullable()->after('comprimento');
            $table->decimal('altura', 8, 2)->nullable()->after('largura');
        });
    }

    public function down(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->dropColumn(['comprimento', 'largura', 'altura']);
        });
    }
};
