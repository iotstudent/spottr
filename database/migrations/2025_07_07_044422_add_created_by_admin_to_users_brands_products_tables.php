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
            $table->boolean('created_by_admin')->default(false)->after('role');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->boolean('created_by_admin')->default(false)->after('name');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('created_by_admin')->default(false)->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('created_by_admin');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn('created_by_admin');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('created_by_admin');
        });
    }
};
