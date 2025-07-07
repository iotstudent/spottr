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
        Schema::create('corporate_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('industry_id')->nullable()->constrained('industries')->nullOnDelete();
            $table->string('kyc_doc')->nullable();
            $table->string('company_name');
            $table->string('company_size')->nullable();
            $table->string('company_address')->nullable();
            $table->string('company_description');
            $table->string('tags')->nullable();
            $table->string('website_url')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corporate_profiles');
    }
};
