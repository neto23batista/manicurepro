<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('codigo_indicacao', 16)->nullable()->unique()->after('pontos_fidelidade');
            $table->foreignId('indicado_por_cliente_id')
                ->nullable()
                ->after('codigo_indicacao')
                ->constrained('clientes')
                ->nullOnDelete();
        });

        // Backfill códigos únicos para clientes existentes.
        $ids = DB::table('clientes')->whereNull('codigo_indicacao')->pluck('id');
        foreach ($ids as $id) {
            do {
                $codigo = strtoupper(Str::random(8));
            } while (DB::table('clientes')->where('codigo_indicacao', $codigo)->exists());

            DB::table('clientes')->where('id', $id)->update(['codigo_indicacao' => $codigo]);
        }
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('indicado_por_cliente_id');
            $table->dropUnique(['codigo_indicacao']);
            $table->dropColumn('codigo_indicacao');
        });
    }
};
