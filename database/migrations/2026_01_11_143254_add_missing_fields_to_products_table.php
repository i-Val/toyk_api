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
            $table->decimal('lat', 10, 8)->nullable()->after('expiry');
            $table->decimal('lng', 11, 8)->nullable()->after('lat');
            $table->integer('total_views')->default(0)->after('lng');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng', 'total_views']);
        });
    }
};
