<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comissao_pagamentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salao_id');
            $table->unsignedBigInteger('manicure_id');
            $table->date('periodo_inicio');
            $table->date('periodo_fim');
            $table->decimal('valor', 10, 2);
            $table->timestamp('pago_em');
            $table->string('observacao', 500)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->foreign('salao_id')->references('id')->on('saloes')->onDelete('cascade');
            $table->foreign('manicure_id')->references('id')->on('manicures')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->unique(
                ['salao_id', 'manicure_id', 'periodo_inicio', 'periodo_fim'],
                'comissao_pagamentos_periodo_unique',
            );
            $table->index(['salao_id', 'pago_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comissao_pagamentos');
    }
};
