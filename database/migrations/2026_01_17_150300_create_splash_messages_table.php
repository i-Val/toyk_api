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
        Schema::create('splash_messages', function (Blueprint $table) {
            $table->id();
            $table->text('web_message')->nullable();
            $table->string('web_link')->nullable();
            $table->text('app_message')->nullable();
            $table->string('messgae_bg_color')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('splash_messages');
    }
};

