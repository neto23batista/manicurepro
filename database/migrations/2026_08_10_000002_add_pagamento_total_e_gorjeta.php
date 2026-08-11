<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendamentos', function (Blueprint $table) {
            $table->string('mp_cobranca_tipo')->nullable()->after('sinal_valor'); // sinal | total
            $table->string('mp_total_status')->nullable()->after('mp_cobranca_tipo');
            $table->decimal('mp_total_valor', 10, 2)->nullable()->after('mp_total_status');
        });

        Schema::table('comandas', function (Blueprint $table) {
            $table->decimal('gorjeta', 10, 2)->default(0)->after('desconto');
        });
    }

    public function down(): void
    {
        Schema::table('agendamentos', function (Blueprint $table) {
            $table->dropColumn(['mp_cobranca_tipo', 'mp_total_status', 'mp_total_valor']);
        });

        Schema::table('comandas', function (Blueprint $table) {
            $table->dropColumn('gorjeta');
        });
    }
};
