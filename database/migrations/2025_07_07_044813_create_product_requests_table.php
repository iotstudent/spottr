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
        Schema::create('product_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('category_id')->nullable();
            $table->uuid('sub_category_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('weight')->nullable();
            $table->string('dimension')->nullable();
            $table->text('additional_specification')->nullable();
            $table->json('attribute')->nullable();
            $table->json('variants')->nullable();
            $table->string('tags')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->string('product_code')->nullable();
            $table->string('product_image_one')->nullable();
            $table->string('product_image_two')->nullable();
            $table->string('product_image_three')->nullable();
            $table->string('product_image_four')->nullable();
            $table->boolean('is_approved')->nullable();
            $table->text('admin_comment')->nullable();
            $table->timestamps();


            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('sub_category_id')->references('id')->on('sub_categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_requests');
    }
};
