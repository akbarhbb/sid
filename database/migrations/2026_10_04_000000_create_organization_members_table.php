<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->string('organization_type');
            $table->string('name');
            $table->string('position');
            $table->string('photo_path')->nullable();
            $table->date('term_starts_at')->nullable();
            $table->date('term_ends_at')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
            $table->index(['region_id', 'organization_type', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_members');
    }
};
