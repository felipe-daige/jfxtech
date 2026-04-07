<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cupons', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->enum('tipo', ['percentual', 'fixo']);
            $table->decimal('valor', 10, 2);
            $table->decimal('valor_minimo_pedido', 10, 2)->nullable();
            $table->unsignedInteger('limite_usos')->nullable();
            $table->unsignedInteger('usos_realizados')->default(0);
            $table->date('valido_ate')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cupons');
    }
};
