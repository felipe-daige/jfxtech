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
        Schema::create('sorteios', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('slug')->unique();
            $table->string('premio')->nullable();
            $table->text('descricao')->nullable();
            $table->string('instagram_post_url')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('ativo')->default(true);
            $table->unsignedInteger('numero_inicial')->default(1);
            $table->unsignedInteger('max_participantes')->nullable();
            $table->unsignedBigInteger('ganhador_participante_id')->nullable();
            $table->timestamp('resultado_publicado_at')->nullable();
            $table->timestamps();

            $table->index(['ativo', 'starts_at', 'ends_at']);
        });

        Schema::create('sorteio_participantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sorteio_id')->constrained('sorteios')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('numero');
            $table->string('instagram_username', 30);
            $table->string('instagram_friend_1', 30);
            $table->string('instagram_friend_2', 30);
            $table->string('status', 30)->default('pendente');
            $table->timestamp('accepted_rules_at')->nullable();
            $table->timestamp('instagram_requirements_accepted_at')->nullable();
            $table->timestamp('marketing_opt_in_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('audited_at')->nullable();
            $table->text('audit_notes')->nullable();
            $table->timestamps();

            $table->unique(['sorteio_id', 'user_id']);
            $table->unique(['sorteio_id', 'numero']);
            $table->unique(['sorteio_id', 'instagram_username']);
            $table->index(['sorteio_id', 'status']);
        });

        Schema::table('sorteios', function (Blueprint $table) {
            $table->foreign('ganhador_participante_id')
                ->references('id')
                ->on('sorteio_participantes')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sorteios', function (Blueprint $table) {
            $table->dropForeign(['ganhador_participante_id']);
        });

        Schema::dropIfExists('sorteio_participantes');
        Schema::dropIfExists('sorteios');
    }
};
