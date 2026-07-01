<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vales_presente', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salao_id');
            $table->string('codigo', 20)->unique();
            $table->decimal('valor', 10, 2);
            $table->decimal('saldo', 10, 2);
            $table->string('comprador_nome')->nullable();
            $table->string('comprador_contato')->nullable();
            $table->string('beneficiario_nome')->nullable();
            $table->string('mensagem', 500)->nullable();
            $table->date('validade')->nullable();
            $table->string('status', 20)->default('ativo'); // ativo | usado | cancelado
            $table->timestamps();

            $table->foreign('salao_id')->references('id')->on('saloes')->onDelete('cascade');
            $table->index(['salao_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vales_presente');
    }
};
