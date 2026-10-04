<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('neighborhood')->nullable();
            $table->string('district')->default('Sawangan');
            $table->string('city')->default('Depok');
            $table->string('province')->default('Jawa Barat');
            $table->string('postal_code', 10)->nullable();
            $table->string('slug')->unique();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
