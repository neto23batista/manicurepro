<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fornecedores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salao_id');
            $table->string('nome');
            $table->string('contato')->nullable();
            $table->string('telefone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('documento', 30)->nullable();
            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->foreign('salao_id')->references('id')->on('saloes')->onDelete('cascade');
            $table->index(['salao_id', 'ativo']);
        });

        Schema::table('produtos', function (Blueprint $table) {
            $table->unsignedBigInteger('fornecedor_id')->nullable()->after('salao_id');
            $table->foreign('fornecedor_id')->references('id')->on('fornecedores')->nullOnDelete();
        });

        $this->expandirTiposMovimentacao();
    }

    public function down(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->dropForeign(['fornecedor_id']);
            $table->dropColumn('fornecedor_id');
        });

        Schema::dropIfExists('fornecedores');
    }

    /**
     * Libera perda / consumo_interno / devolucao além de entrada|saida|ajuste.
     * Em SQLite o enum vira string + CHECK — recria a coluna como string(30).
     */
    private function expandirTiposMovimentacao(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE estoque_movimentacoes MODIFY COLUMN tipo ENUM('entrada','saida','ajuste','perda','consumo_interno','devolucao') NOT NULL");

            return;
        }

        Schema::table('estoque_movimentacoes', function (Blueprint $table) {
            $table->string('tipo_tmp', 30)->nullable();
        });

        DB::table('estoque_movimentacoes')->orderBy('id')->each(function ($row) {
            DB::table('estoque_movimentacoes')
                ->where('id', $row->id)
                ->update(['tipo_tmp' => $row->tipo]);
        });

        Schema::table('estoque_movimentacoes', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });

        Schema::table('estoque_movimentacoes', function (Blueprint $table) {
            $table->string('tipo', 30)->after('user_id');
        });

        DB::table('estoque_movimentacoes')->orderBy('id')->each(function ($row) {
            DB::table('estoque_movimentacoes')
                ->where('id', $row->id)
                ->update(['tipo' => $row->tipo_tmp]);
        });

        Schema::table('estoque_movimentacoes', function (Blueprint $table) {
            $table->dropColumn('tipo_tmp');
        });
    }
};
