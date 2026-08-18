<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_devices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('network_account_id')->nullable()->constrained('network_accounts')->nullOnDelete();
            $table->enum('type', ['router', 'ont', 'modem', 'mikrotik', 'access_point', 'other'])->default('other');
            $table->string('mac_address', 17)->nullable()->index();
            $table->string('serial_number')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
            $table->timestamp('last_seen_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_devices');
    }
};
