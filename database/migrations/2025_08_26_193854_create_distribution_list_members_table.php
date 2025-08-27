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
        Schema::create('distribution_list_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('distribution_list_id');
            $table->uuid('member_id');
            $table->timestamps();

            $table->foreign('distribution_list_id')->references('id')->on('distribution_lists')->onDelete('cascade');
            $table->foreign('member_id')->references('id')->on('memberships')->onDelete('cascade'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribution_list_members');
    }
};
