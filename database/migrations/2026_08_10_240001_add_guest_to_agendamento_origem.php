<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE agendamentos MODIFY COLUMN origem ENUM('web','app','balcao','telefone','guest') NOT NULL DEFAULT 'web'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE agendamentos SET origem = 'web' WHERE origem = 'guest'");
        DB::statement("ALTER TABLE agendamentos MODIFY COLUMN origem ENUM('web','app','balcao','telefone') NOT NULL DEFAULT 'web'");
    }
};
