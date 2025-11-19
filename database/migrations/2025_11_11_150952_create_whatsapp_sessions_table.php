<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->string('phone_number')->nullable();
            $table->string('qr_code')->nullable();
            $table->enum('status', ['disconnected', 'connecting', 'connected', 'qr_ready'])->default('disconnected');
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->timestamp('last_activity')->nullable();
            $table->timestamps();

            $table->foreign('agent_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_sessions');
    }
};