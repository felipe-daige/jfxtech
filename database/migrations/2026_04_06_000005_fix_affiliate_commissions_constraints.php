<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('affiliate_commissions', function (Blueprint $table) {
            // Fix 1: make eligible_at nullable
            $table->timestamp('eligible_at')->nullable()->change();

            // Fix 2: change pedido_id FK from CASCADE to RESTRICT
            $table->dropForeign(['pedido_id']);
            $table->foreign('pedido_id')->references('id')->on('pedidos')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_commissions', function (Blueprint $table) {
            $table->timestamp('eligible_at')->nullable(false)->change();
            $table->dropForeign(['pedido_id']);
            $table->foreign('pedido_id')->references('id')->on('pedidos')->cascadeOnDelete();
        });
    }
};
