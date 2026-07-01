<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Defensivo: remove comandas duplicadas do mesmo agendamento (mantém a mais antiga)
        // antes de criar o índice — sem isso a migration falharia em bases já afetadas.
        $duplicadas = DB::table('comandas')
            ->select('agendamento_id', DB::raw('MIN(id) as manter'))
            ->whereNotNull('agendamento_id')
            ->groupBy('agendamento_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicadas as $dup) {
            DB::table('comandas')
                ->where('agendamento_id', $dup->agendamento_id)
                ->where('id', '!=', $dup->manter)
                ->delete();
        }

        Schema::table('comandas', function (Blueprint $table) {
            $table->unique('agendamento_id');
        });
    }

    public function down(): void
    {
        Schema::table('comandas', function (Blueprint $table) {
            $table->dropUnique(['agendamento_id']);
        });
    }
};
