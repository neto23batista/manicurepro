<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendamentos', function (Blueprint $table) {
            $table->string('mp_payment_id')->nullable()->index()->after('lembrete_2h_em');
            // null = sem sinal | pendente | pago | rejeitado | cancelado | estornado
            $table->string('sinal_status')->nullable()->after('mp_payment_id');
            $table->decimal('sinal_valor', 10, 2)->nullable()->after('sinal_status');
        });
    }

    public function down(): void
    {
        Schema::table('agendamentos', function (Blueprint $table) {
            $table->dropColumn(['mp_payment_id', 'sinal_status', 'sinal_valor']);
        });
    }
};
