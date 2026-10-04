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
            $table->string('occupation')->nullable()->after('house_number');
            $table->string('residency_status')->default('resident')->after('account_status');
            $table->boolean('is_adult')->default(false)->after('residency_status');
            $table->date('moved_out_at')->nullable()->after('consented_at');
            $table->timestamp('data_retained_until')->nullable()->after('moved_out_at');
        });

        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('relationship')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('occupation')->nullable();
            $table->boolean('is_adult')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_members');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['occupation', 'residency_status', 'is_adult', 'moved_out_at', 'data_retained_until']);
        });
    }
};
