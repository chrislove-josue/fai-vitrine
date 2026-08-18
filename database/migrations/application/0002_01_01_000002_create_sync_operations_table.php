<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_operations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('operation_type', 60);
            $table->string('entity_type', 60);
            $table->uuid('entity_uuid')->index();
            $table->string('source', 60)->default('laravel');
            $table->string('destination', 60);
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->string('error_code', 60)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('next_retry_at')->nullable()->index();
            $table->timestamps();

            $table->index(['entity_type', 'entity_uuid']);
            $table->index('destination');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_operations');
    }
};
