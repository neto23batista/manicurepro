<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disponibilidades_manicure', function (Blueprint $table) {
            $table->time('pausa_inicio')->nullable()->after('hora_fim');
            $table->time('pausa_fim')->nullable()->after('pausa_inicio');
        });
    }

    public function down(): void
    {
        Schema::table('disponibilidades_manicure', function (Blueprint $table) {
            $table->dropColumn(['pausa_inicio', 'pausa_fim']);
        });
    }
};
