<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cupom_pagamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cupom_id')->constrained('cupons')->cascadeOnDelete();
            $table->decimal('valor_pago', 10, 2);
            $table->text('observacao')->nullable();
            $table->date('pago_em');
            $table->timestamps();
        });

        Schema::table('cupom_usos', function (Blueprint $table) {
            $table->foreignId('cupom_pagamento_id')
                ->nullable()
                ->after('pedido_id')
                ->constrained('cupom_pagamentos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cupom_usos', function (Blueprint $table) {
            $table->dropForeign(['cupom_pagamento_id']);
            $table->dropColumn('cupom_pagamento_id');
        });

        Schema::dropIfExists('cupom_pagamentos');
    }
};
