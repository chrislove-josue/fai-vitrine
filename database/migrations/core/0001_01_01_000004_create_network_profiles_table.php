<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_profiles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->unsignedBigInteger('download_speed')->default(0);
            $table->unsignedBigInteger('upload_speed')->default(0);
            $table->unsignedBigInteger('rate_limit')->nullable();
            $table->unsignedBigInteger('burst_limit')->nullable();
            $table->unsignedBigInteger('burst_threshold')->nullable();
            $table->unsignedBigInteger('burst_time')->nullable();
            $table->unsignedTinyInteger('priority')->default(0);
            $table->unsignedBigInteger('session_timeout')->nullable();
            $table->unsignedBigInteger('idle_timeout')->nullable();
            $table->unsignedBigInteger('data_limit')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_profiles');
    }
};
