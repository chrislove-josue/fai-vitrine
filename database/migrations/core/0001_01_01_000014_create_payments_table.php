<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('payment_reference', 60)->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->unsignedBigInteger('amount')->default(0);
            $table->string('currency', 3)->default('XOF');
            $table->enum('method', ['mobile_money', 'card', 'bank_transfer', 'cash', 'manual'])->default('manual');
            $table->string('provider', 40)->nullable();
            $table->enum('status', ['pending', 'processing', 'successful', 'failed', 'cancelled', 'refunded', 'partially_refunded'])->default('pending')->index();
            $table->string('transaction_id', 64)->nullable()->index();
            $table->string('provider_reference', 64)->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('invoice_id');
            $table->index('subscription_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
