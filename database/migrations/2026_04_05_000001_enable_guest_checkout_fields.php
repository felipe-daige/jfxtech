<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('customer_name')->nullable()->after('frete_valor');
            $table->string('customer_email')->nullable()->after('customer_name');
            $table->string('customer_phone', 20)->nullable()->after('customer_email');
            $table->string('guest_token')->nullable()->unique()->after('customer_phone');
            $table->string('checkout_mode', 20)->nullable()->after('guest_token');
        });

        Schema::table('enderecos', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('enderecos', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });

        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropUnique(['guest_token']);
            $table->dropColumn([
                'customer_name',
                'customer_email',
                'customer_phone',
                'guest_token',
                'checkout_mode',
            ]);
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
