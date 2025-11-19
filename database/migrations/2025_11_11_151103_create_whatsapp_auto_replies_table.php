<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_auto_replies', function (Blueprint $table) {
            $table->id();
            $table->string('trigger_keyword')->nullable();
            $table->text('reply_message');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_greeting')->default(false);
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_auto_replies');
    }
};