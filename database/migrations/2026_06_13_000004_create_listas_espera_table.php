<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listas_espera', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salao_id')->constrained('saloes')->cascadeOnDelete();
            $table->foreignId('manicure_id')->nullable()->constrained('manicures')->nullOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('data_preferida')->nullable();
            $table->string('periodo')->nullable(); // manha | tarde | noite | qualquer
            $table->string('status')->default('aguardando'); // aguardando | notificado | atendido | cancelado
            $table->timestamp('notificado_em')->nullable();
            $table->timestamps();

            $table->index(['salao_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listas_espera');
    }
};
