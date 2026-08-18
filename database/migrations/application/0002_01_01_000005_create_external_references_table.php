<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_references', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('external_system_id')->constrained('external_systems')->cascadeOnDelete();
            $table->string('entity_type', 60);
            $table->uuid('entity_uuid')->index();
            $table->string('external_id');
            $table->string('external_reference')->nullable();
            $table->string('sync_status', 20)->default('pending')->index();
            $table->timestamp('last_synced_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['external_system_id', 'entity_type', 'entity_uuid'], 'ext_ref_system_entity_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_references');
    }
};
