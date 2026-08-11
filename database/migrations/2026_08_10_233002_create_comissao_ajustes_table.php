<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comissao_ajustes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salao_id')->constrained('saloes')->cascadeOnDelete();
            $table->foreignId('manicure_id')->constrained('manicures')->cascadeOnDelete();
            $table->date('periodo_inicio');
            $table->date('periodo_fim');
            $table->decimal('valor', 10, 2);
            $table->string('motivo')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['salao_id', 'manicure_id', 'periodo_inicio', 'periodo_fim'], 'comissao_ajustes_periodo_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comissao_ajustes');
    }
};
