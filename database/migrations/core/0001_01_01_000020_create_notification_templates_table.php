<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 60)->unique();
            $table->enum('channel', ['email', 'sms', 'push', 'whatsapp'])->default('email');
            $table->string('subject')->nullable();
            $table->text('content');
            $table->json('variables')->nullable();
            $table->unsignedTinyInteger('version')->default(1);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
