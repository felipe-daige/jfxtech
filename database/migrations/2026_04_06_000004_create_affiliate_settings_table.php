<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('affiliate_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->timestamps();
        });

        DB::table('affiliate_settings')->insert([
            ['key' => 'commission_percent_default', 'value' => '5.00', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'cookie_days',                'value' => '30',   'created_at' => now(), 'updated_at' => now()],
            ['key' => 'grace_period_days',          'value' => '30',   'created_at' => now(), 'updated_at' => now()],
            ['key' => 'commission_trigger',         'value' => 'first_paid_order', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_settings');
    }
};
