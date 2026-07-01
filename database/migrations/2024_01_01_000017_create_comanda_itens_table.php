<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comanda_itens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('comanda_id');
            $table->enum('tipo', ['servico', 'produto']);
            $table->unsignedBigInteger('servico_id')->nullable();
            $table->unsignedBigInteger('produto_id')->nullable();
            $table->string('descricao');
            $table->decimal('quantidade', 10, 3)->default(1);
            $table->decimal('preco_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();

            $table->foreign('comanda_id')->references('id')->on('comandas')->onDelete('cascade');
            $table->foreign('servico_id')->references('id')->on('servicos')->onDelete('set null');
            $table->foreign('produto_id')->references('id')->on('produtos')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comanda_itens');
    }
};
