<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cupons', function (Blueprint $table) {
            $table->string('origem', 30)->default('manual')->after('ativo');
            $table->boolean('primeira_compra')->default(false)->after('origem');
            $table->unsignedBigInteger('cliente_id')->nullable()->after('primeira_compra');
            $table->unsignedBigInteger('servico_id')->nullable()->after('cliente_id');
            $table->unsignedInteger('uso_maximo_por_cliente')->nullable()->after('uso_atual');
            $table->boolean('anti_stacking_fidelidade')->default(false)->after('uso_maximo_por_cliente');

            $table->foreign('cliente_id')->references('id')->on('clientes')->nullOnDelete();
            $table->foreign('servico_id')->references('id')->on('servicos')->nullOnDelete();
        });

        Schema::table('fidelidade_pontos', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('descricao');
            $table->index(['cliente_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('fidelidade_pontos', function (Blueprint $table) {
            $table->dropIndex(['cliente_id', 'expires_at']);
            $table->dropColumn('expires_at');
        });

        Schema::table('cupons', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropForeign(['servico_id']);
            $table->dropColumn([
                'origem',
                'primeira_compra',
                'cliente_id',
                'servico_id',
                'uso_maximo_por_cliente',
                'anti_stacking_fidelidade',
            ]);
        });
    }
};
