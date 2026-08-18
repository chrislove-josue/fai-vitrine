<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft')->index();
            $table->unsignedInteger('duration_days')->default(30);
            $table->foreignId('network_profile_id')->constrained('network_profiles');
            $table->unsignedBigInteger('activation_fee')->default(0);
            $table->string('currency', 3)->default('XOF');
            $table->unsignedInteger('max_simultaneous_sessions')->default(1);
            $table->unsignedBigInteger('data_limit')->nullable();
            $table->unsignedBigInteger('fair_use_limit')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
