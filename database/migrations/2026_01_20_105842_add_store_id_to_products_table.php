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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'store_id')) {
                $table->unsignedBigInteger('store_id')->nullable()->after('user_id');
            }
            // Add foreign key constraint separately to be safe or if column existed but key didn't
            // Check if foreign key exists? Hard to check in simple migration.
            // We assume if column existed from failed migration, key didn't.
            try {
                $table->foreign('store_id')->references('id')->on('stores')->onDelete('set null');
            } catch (\Exception $e) {
                // Ignore if key already exists (unlikely given the error)
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
        });
    }
};
