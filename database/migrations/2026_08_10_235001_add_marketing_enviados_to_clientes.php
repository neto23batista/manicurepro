<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->timestamp('reativacao_enviada_em')->nullable()->after('aniversario_enviado_em');
            $table->timestamp('retorno_sugerido_em')->nullable()->after('reativacao_enviada_em');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['reativacao_enviada_em', 'retorno_sugerido_em']);
        });
    }
};
