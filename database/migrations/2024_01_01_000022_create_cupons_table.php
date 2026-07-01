<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cupons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salao_id');
            $table->string('codigo', 30)->unique();
            $table->enum('tipo', ['percentual', 'fixo'])->default('percentual');
            $table->decimal('valor', 10, 2);
            $table->decimal('minimo_pedido', 10, 2)->default(0);
            $table->decimal('maximo_desconto', 10, 2)->nullable();
            $table->integer('uso_maximo')->nullable();
            $table->integer('uso_atual')->default(0);
            $table->date('validade')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->foreign('salao_id')->references('id')->on('saloes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cupons');
    }
};
