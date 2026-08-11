<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->unsignedInteger('total_faltas')->default(0)->after('pontos_fidelidade');
        });

        foreach (DB::table('clientes')->pluck('id') as $id) {
            $count = DB::table('agendamentos')
                ->where('cliente_id', $id)
                ->where('status', 'nao_compareceu')
                ->count();

            DB::table('clientes')->where('id', $id)->update(['total_faltas' => $count]);
        }
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('total_faltas');
        });
    }
};
