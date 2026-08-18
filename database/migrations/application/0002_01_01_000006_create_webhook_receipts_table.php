<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_receipts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('external_system_id')->nullable()->constrained('external_systems')->nullOnDelete();
            $table->string('event', 80);
            $table->string('external_id')->nullable();
            $table->string('signature')->nullable();
            $table->longText('payload')->nullable();
            $table->string('status', 20)->default('received')->index();
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['event', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_receipts');
    }
};
