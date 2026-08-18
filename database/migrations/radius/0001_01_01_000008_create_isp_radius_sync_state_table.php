<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('isp_radius_sync_state', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('network_account_uuid')->unique();
            $table->uuid('subscription_uuid')->nullable();
            $table->enum('desired_status', ['active', 'grace_period', 'suspended', 'terminated'])->default('active');
            $table->enum('actual_status', ['active', 'suspended', 'terminated', 'unknown'])->default('unknown');
            $table->string('desired_profile', 64)->nullable();
            $table->string('actual_profile', 64)->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->enum('sync_status', ['synced', 'pending', 'error', 'retry'])->default('pending')->index();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isp_radius_sync_state');
    }
};
