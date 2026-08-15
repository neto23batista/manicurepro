<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendamentos', function (Blueprint $table) {
            $table->string('mp_gorjeta_status', 20)->nullable()->after('mp_total_valor');
            $table->decimal('mp_gorjeta_valor', 10, 2)->nullable()->after('mp_gorjeta_status');
        });
    }

    public function down(): void
    {
        Schema::table('agendamentos', function (Blueprint $table) {
            $table->dropColumn(['mp_gorjeta_status', 'mp_gorjeta_valor']);
        });
    }
};
