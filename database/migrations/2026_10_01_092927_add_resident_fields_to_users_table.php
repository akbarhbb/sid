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
            $table->string('phone', 30)->nullable()->unique();
            $table->string('address')->nullable();
            $table->string('house_number', 30)->nullable();
            $table->text('national_id')->nullable();
            $table->text('family_card_number')->nullable();
            $table->string('role')->default('warga');
            $table->string('account_status')->default('pending');
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('consented_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('region_id');
            $table->dropColumn(['phone', 'address', 'house_number', 'national_id', 'family_card_number', 'role', 'account_status', 'consented_at']);
        });
    }
};
