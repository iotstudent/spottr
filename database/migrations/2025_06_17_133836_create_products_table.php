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
        Schema::create('products', function (Blueprint $table) {
            
           $table->uuid('id')->primary();

            // Foreign UUIDs with constraints
            $table->foreignUuid('brand_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignUuid('corporate_profile_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('category_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignUuid('sub_category_id')->nullable()->constrained('sub_categories')->onDelete('set null');

            // Product fields
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('weight')->nullable();
            $table->string('dimension')->nullable();
            $table->text('additional_specification')->nullable();
            $table->json('attribute')->nullable();
            $table->json('variants')->nullable();
            $table->string('tags')->nullable();
            $table->boolean('is_available')->default(true);
            $table->decimal('price', 12, 2);
            $table->string('product_code')->unique();

            // Images
            $table->string('product_image_1')->nullable();
            $table->string('product_image_2')->nullable();
            $table->string('product_image_3')->nullable();
            $table->string('product_image_4')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
