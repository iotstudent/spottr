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
        Schema::create('representatives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('corporate_profile_id')->constrained()->onDelete('cascade');
            $table->string('job_title')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('pic')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->text('bio')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('representatives');
    }
};
