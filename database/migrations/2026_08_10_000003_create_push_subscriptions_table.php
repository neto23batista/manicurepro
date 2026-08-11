<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Endpoints FCM/Mozilla costumam passar de 255 chars
            $table->string('endpoint', 500)->unique();
            $table->string('public_key');   // keys.p256dh
            $table->string('auth_token');   // keys.auth
            $table->string('content_encoding', 32)->default('aesgcm');
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
