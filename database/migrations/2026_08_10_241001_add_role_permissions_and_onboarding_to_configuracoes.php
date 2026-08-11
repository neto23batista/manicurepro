<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_salao', function (Blueprint $table) {
            $table->json('role_permissions')->nullable()->after('limite_alerta_no_show');
            $table->timestamp('onboarding_completed_at')->nullable()->after('role_permissions');
            $table->timestamp('onboarding_dismissed_at')->nullable()->after('onboarding_completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes_salao', function (Blueprint $table) {
            $table->dropColumn([
                'role_permissions',
                'onboarding_completed_at',
                'onboarding_dismissed_at',
            ]);
        });
    }
};
