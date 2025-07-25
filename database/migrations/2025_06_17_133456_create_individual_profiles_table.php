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
        Schema::create('individual_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuId('user_id')->constrained()->onDelete('cascade');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('address')->nullable();
            $table->string('store_address')->nullable();
            $table->string('store_name')->nullable();
            $table->longText('store_desc')->nullable();
            $table->string('store_phone')->nullable();
            $table->string('store_bg_image')->nullable();
            $table->string('store_profile_image')->nullable();
            $table->longText('bio')->nullable();
            $table->enum('type', ['seller','buyer']);
            $table->enum('verification_level', ['0','1','2','3']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('individual_profiles');
    }
};
