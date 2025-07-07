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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('fiat_wallet', 15, 2)->default(0)->after('verification_expiry');
            $table->decimal('crypto_wallet', 30, 8)->default(0)->after('fiat_wallet');
            $table->string('transaction_pin')->nullable()->after('crypto_wallet');
              $table->string('transaction_pin_otp')->nullable()->after('transaction_pin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['fiat_wallet', 'crypto_wallet', 'transaction_pin']);
        });
    }
};
