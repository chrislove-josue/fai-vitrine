<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('isp_radius_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('customer_uuid')->nullable()->index()->comment('Référence logique isp_core.customers.uuid — sans FK.');
            $table->uuid('network_account_uuid')->index()->comment('Référence logique isp_core.network_accounts.uuid — sans FK.');
            $table->string('username', 64)->unique();
            $table->uuid('subscription_uuid')->nullable()->index();
            $table->uuid('network_profile_uuid')->nullable();
            $table->enum('status', ['pending', 'active', 'suspended', 'disabled', 'terminated'])->default('pending')->index();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('isp_radius_accounts');
    }
};
