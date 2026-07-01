<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendamentos', function (Blueprint $table) {
            $table->timestamp('confirmado_em')->nullable()->after('status');
            $table->timestamp('lembrete_24h_em')->nullable()->after('confirmado_em');
            $table->timestamp('lembrete_2h_em')->nullable()->after('lembrete_24h_em');
        });
    }

    public function down(): void
    {
        Schema::table('agendamentos', function (Blueprint $table) {
            $table->dropColumn(['confirmado_em', 'lembrete_24h_em', 'lembrete_2h_em']);
        });
    }
};
