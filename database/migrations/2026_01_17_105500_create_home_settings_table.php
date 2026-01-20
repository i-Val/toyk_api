<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('home_settings')) {
            Schema::create('home_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('free_posts')->default(false);
                $table->boolean('allow_sms')->default(false);
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('w_phone')->nullable();
                $table->string('address')->nullable();
                $table->string('facebook')->nullable();
                $table->string('twitter')->nullable();
                $table->string('instagram')->nullable();
                $table->string('youtube')->nullable();
                $table->string('linkdin')->nullable();
                $table->string('app_version')->nullable();
                $table->string('ios_version')->nullable();
                $table->json('top_categories')->nullable();
                $table->json('nav_categories')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('home_settings');
    }
};

