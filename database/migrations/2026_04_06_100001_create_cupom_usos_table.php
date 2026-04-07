<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cupom_usos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cupom_id')->constrained('cupons')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cupom_usos');
    }
};
