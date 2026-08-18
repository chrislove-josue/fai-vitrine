<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('username', 100)->unique();
            $table->enum('authentication_type', ['pap', 'chap', 'mschapv2', 'mac'])->default('pap');
            $table->enum('status', ['pending', 'active', 'suspended', 'disabled', 'terminated'])->default('pending')->index();
            $table->boolean('mac_auth_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_accounts');
    }
};
