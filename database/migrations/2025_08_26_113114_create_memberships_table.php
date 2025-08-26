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
        Schema::create('memberships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('corporate_id'); // references users table (role = corporate)
            $table->uuid('seller_id');    // references users table (role = individual, type = seller)
            $table->enum('status', ['pending', 'accepted', 'rejected', 'revoked'])->default('pending');
            $table->enum('initiated_by', ['corporate', 'seller']);
            $table->timestamps();

            $table->foreign('corporate_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
