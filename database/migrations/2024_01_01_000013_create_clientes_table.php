<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('salao_id');
            $table->string('nome');
            $table->string('email')->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('cpf', 14)->nullable();
            $table->date('data_nascimento')->nullable();
            $table->string('endereco')->nullable();
            $table->text('observacoes')->nullable();
            $table->text('alergias')->nullable();
            $table->integer('total_visitas')->default(0);
            $table->decimal('total_gasto', 10, 2)->default(0);
            $table->integer('pontos_fidelidade')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('salao_id')->references('id')->on('saloes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
