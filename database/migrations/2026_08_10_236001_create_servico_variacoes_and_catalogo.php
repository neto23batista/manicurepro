<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servicos', function (Blueprint $table) {
            $table->string('imagem')->nullable()->after('descricao');
            $table->decimal('custo_estimado', 10, 2)->nullable()->after('preco');
        });

        Schema::create('servico_variacoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('servico_id');
            $table->string('nome'); // Básica, Gel, Fibra…
            $table->decimal('preco', 10, 2);
            $table->unsignedInteger('duracao')->default(30);
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->foreign('servico_id')->references('id')->on('servicos')->onDelete('cascade');
            $table->index(['servico_id', 'ativo']);
        });

        Schema::table('agendamento_servicos', function (Blueprint $table) {
            $table->unsignedBigInteger('servico_variacao_id')->nullable()->after('servico_id');
            $table->foreign('servico_variacao_id')
                ->references('id')
                ->on('servico_variacoes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('agendamento_servicos', function (Blueprint $table) {
            $table->dropForeign(['servico_variacao_id']);
            $table->dropColumn('servico_variacao_id');
        });

        Schema::dropIfExists('servico_variacoes');

        Schema::table('servicos', function (Blueprint $table) {
            $table->dropColumn(['imagem', 'custo_estimado']);
        });
    }
};
