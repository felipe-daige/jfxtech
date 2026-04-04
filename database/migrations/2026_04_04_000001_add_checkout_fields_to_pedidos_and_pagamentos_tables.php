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
        Schema::table('pedidos', function (Blueprint $table) {
            $table->string('frete_tipo', 20)->nullable()->after('valor_total');
            $table->decimal('frete_valor', 10, 2)->default(0)->after('frete_tipo');
        });

        Schema::table('pagamentos', function (Blueprint $table) {
            $table->string('gateway', 50)->nullable()->after('metodo');
            $table->string('gateway_payment_id')->nullable()->after('gateway');
            $table->string('gateway_status_detail')->nullable()->after('gateway_payment_id');
            $table->string('external_reference')->nullable()->after('gateway_status_detail');
            $table->json('payload')->nullable()->after('external_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pagamentos', function (Blueprint $table) {
            $table->dropColumn([
                'gateway',
                'gateway_payment_id',
                'gateway_status_detail',
                'external_reference',
                'payload',
            ]);
        });

        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn(['frete_tipo', 'frete_valor']);
        });
    }
};
