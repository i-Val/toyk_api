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
            $table->string('country')->nullable()->after('contact');
            $table->string('state')->nullable()->after('country');
            $table->string('city')->nullable()->after('state');
            $table->text('address')->nullable()->after('city');
            $table->string('zipcode')->nullable()->after('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['country', 'state', 'city', 'address', 'zipcode']);
        });
    }
};
