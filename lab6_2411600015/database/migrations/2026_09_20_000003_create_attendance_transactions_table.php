<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_transactions', function (Blueprint $table) {
            $table->id();

            // The ledger has no meaning without its course, so it is removed with it
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();

            // Keep the audit row even if the user account is deleted
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // absence | restore | adjustment
            $table->string('type', 20);

            // Signed: negative uses allowance, positive restores it
            $table->integer('quantity');

            $table->string('reference_number', 50)->nullable();
            $table->string('reason', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Speeds up a course's history and date-range filters
            $table->index(['course_id', 'created_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_transactions');
    }
};
