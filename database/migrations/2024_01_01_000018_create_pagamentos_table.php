<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagamentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('comanda_id')->nullable();
            $table->unsignedBigInteger('agendamento_id')->nullable();
            $table->unsignedBigInteger('salao_id');
            $table->enum('forma', ['dinheiro', 'cartao_credito', 'cartao_debito', 'pix', 'transferencia', 'voucher'])->default('dinheiro');
            $table->decimal('valor', 10, 2);
            $table->enum('status', ['pendente', 'confirmado', 'cancelado', 'estornado'])->default('pendente');
            $table->string('referencia')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->foreign('comanda_id')->references('id')->on('comandas')->onDelete('set null');
            $table->foreign('agendamento_id')->references('id')->on('agendamentos')->onDelete('set null');
            $table->foreign('salao_id')->references('id')->on('saloes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamentos');
    }
};
