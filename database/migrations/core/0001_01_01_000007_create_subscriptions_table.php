<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('subscription_number', 40)->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('offer_id')->constrained('offers');
            $table->enum('status', ['pending', 'active', 'grace_period', 'suspended', 'expired', 'cancelled', 'terminated'])->default('pending')->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->unsignedBigInteger('price')->default(0);
            $table->string('currency', 3)->default('XOF');
            $table->timestamp('next_renewal_at')->nullable();
            $table->string('suspension_reason')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->string('termination_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
