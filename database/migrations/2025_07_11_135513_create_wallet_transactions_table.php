<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('tx_ref')->unique(); // Unique transaction reference from spottr
            $table->string('transaction_id')->nullable(); // Payment provider gateway transaction id
            $table->enum('type', ['credit', 'debit']); // Credit or Debit
            $table->enum('format', ['fiat', 'crypto']); // Fiat or Crypto
            $table->string('provider')->nullable(); // e.g., Flutterwave, Paystack
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->default('NGN');
            $table->enum('payment_status', ['pending', 'successful', 'failed'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
