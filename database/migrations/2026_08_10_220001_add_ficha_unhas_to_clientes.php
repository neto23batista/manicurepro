<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->text('notas_unhas')->nullable()->after('alergias');
            $table->text('cores_preferidas')->nullable()->after('notas_unhas');
            $table->text('contraindicacoes')->nullable()->after('cores_preferidas');
            $table->text('ultima_formula')->nullable()->after('contraindicacoes');
        });

        Schema::create('cliente_ficha_historico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('salao_id')->constrained('saloes')->cascadeOnDelete();
            $table->foreignId('agendamento_id')->nullable()->constrained('agendamentos')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notas')->nullable();
            $table->text('cores')->nullable();
            $table->text('formula')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_ficha_historico');

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn([
                'notas_unhas',
                'cores_preferidas',
                'contraindicacoes',
                'ultima_formula',
            ]);
        });
    }
};
