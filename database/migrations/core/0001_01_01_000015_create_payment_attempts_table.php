<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->string('provider', 40)->nullable();
            $table->string('request_id', 64)->nullable();
            $table->unsignedBigInteger('amount')->default(0);
            $table->string('currency', 3)->default('XOF');
            $table->enum('status', ['pending', 'processing', 'successful', 'failed', 'cancelled'])->default('pending');
            $table->string('response_code', 30)->nullable();
            $table->string('response_message')->nullable();
            $table->string('provider_reference', 64)->nullable();
            $table->json('provider_payload')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};
