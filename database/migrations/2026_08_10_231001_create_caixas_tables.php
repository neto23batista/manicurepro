<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caixas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salao_id');
            $table->unsignedBigInteger('aberto_por')->nullable();
            $table->unsignedBigInteger('fechado_por')->nullable();
            $table->decimal('saldo_inicial', 10, 2)->default(0);
            $table->decimal('saldo_final_informado', 10, 2)->nullable();
            $table->decimal('saldo_calculado', 10, 2)->nullable();
            $table->decimal('diferenca', 10, 2)->nullable();
            $table->timestamp('aberto_em');
            $table->timestamp('fechado_em')->nullable();
            $table->string('observacao', 500)->nullable();
            $table->timestamps();

            $table->foreign('salao_id')->references('id')->on('saloes')->onDelete('cascade');
            $table->foreign('aberto_por')->references('id')->on('users')->onDelete('set null');
            $table->foreign('fechado_por')->references('id')->on('users')->onDelete('set null');

            $table->index(['salao_id', 'aberto_em']);
            $table->index(['salao_id', 'fechado_em']);
        });

        Schema::create('caixa_movimentacoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('caixa_id');
            $table->string('tipo', 20); // entrada | saida | sangria | suprimento
            $table->decimal('valor', 10, 2);
            $table->string('descricao', 255);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('pagamento_id')->nullable();
            $table->timestamps();

            $table->foreign('caixa_id')->references('id')->on('caixas')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('pagamento_id')->references('id')->on('pagamentos')->onDelete('set null');

            $table->index(['caixa_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caixa_movimentacoes');
        Schema::dropIfExists('caixas');
    }
};
