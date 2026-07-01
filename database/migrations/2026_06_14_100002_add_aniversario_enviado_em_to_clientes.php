<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Marcador explícito da felicitação de aniversário (idempotência por ano).
            $table->date('aniversario_enviado_em')->nullable()->after('data_nascimento');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('aniversario_enviado_em');
        });
    }
};
