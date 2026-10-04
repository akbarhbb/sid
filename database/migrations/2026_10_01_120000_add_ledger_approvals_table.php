<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ledger_transactions', function (Blueprint $table) {
            $table->string('evidence_path')->nullable();
        });

        Schema::create('ledger_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ledger_transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approved_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('approved_at');
            $table->unique(['ledger_transaction_id', 'approved_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_approvals');

        Schema::table('ledger_transactions', function (Blueprint $table) {
            $table->dropColumn('evidence_path');
        });
    }
};
