<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('despesas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salao_id');
            $table->string('descricao', 255);
            $table->string('categoria', 50);
            $table->string('fornecedor', 255)->nullable();
            $table->decimal('valor', 10, 2);
            $table->date('vencimento');
            $table->timestamp('pago_em')->nullable();
            $table->boolean('recorrente')->default(false);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->foreign('salao_id')->references('id')->on('saloes')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->index(['salao_id', 'vencimento']);
            $table->index(['salao_id', 'pago_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('despesas');
    }
};
