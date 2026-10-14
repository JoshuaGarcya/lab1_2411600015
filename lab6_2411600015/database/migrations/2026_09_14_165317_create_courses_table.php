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
    Schema::create('courses', function (Blueprint $table) {
        $table->id();
        $table->string('course_code')->unique();
        $table->string('course_name');
        $table->string('category');
        $table->unsignedTinyInteger('units');
        $table->decimal('grade', 3, 2)->default(0);
        $table->string('instructor');
        $table->unsignedTinyInteger('attendance_rate')->default(0);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
