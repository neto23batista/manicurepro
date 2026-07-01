<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slot_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manicure_id')->constrained('manicures')->cascadeOnDelete();
            $table->dateTime('data_hora_inicio');
            $table->dateTime('data_hora_fim');
            $table->string('token', 64)->index();
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->index(['manicure_id', 'data_hora_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slot_holds');
    }
};
