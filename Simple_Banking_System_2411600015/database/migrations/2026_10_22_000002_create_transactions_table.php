<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            // A transaction means nothing without its account, so it is deleted with it.
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();

            // The database supports "transfer", but the controller only accepts
            // "deposit" and "withdrawal" (as required by the exam).
            $table->enum('type', ['deposit', 'withdrawal', 'transfer']);

            $table->decimal('amount', 15, 2);
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Speeds up an account's history and the "transactions today" count
            $table->index(['account_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
