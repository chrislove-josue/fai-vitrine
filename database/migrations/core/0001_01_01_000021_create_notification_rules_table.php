<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_rules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('event', 60);
            $table->integer('offset_minutes')->default(0);
            $table->enum('channel', ['email', 'sms', 'push', 'whatsapp'])->default('email');
            $table->foreignId('template_id')->constrained('notification_templates')->cascadeOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index(['event', 'channel', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_rules');
    }
};
