<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_salao', function (Blueprint $table) {
            $table->unsignedTinyInteger('limite_alerta_no_show')
                ->default(2)
                ->after('lembrete_horas');
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes_salao', function (Blueprint $table) {
            $table->dropColumn('limite_alerta_no_show');
        });
    }
};
