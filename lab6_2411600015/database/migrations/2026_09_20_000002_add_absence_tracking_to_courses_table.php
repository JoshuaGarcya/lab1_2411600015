<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // Sessions held in the term (used to turn absences into an attendance rate)
            $table->unsignedSmallInteger('total_sessions')->default(30)->after('attendance_rate');

            // "Maximum stock": how many absences the course allows
            $table->unsignedTinyInteger('absences_allowed')->default(6)->after('total_sessions');

            // "Quantity on hand": absences still available before the limit
            $table->unsignedTinyInteger('absences_remaining')->default(6)->after('absences_allowed');

            // "Reorder level": warn when remaining absences fall to this number
            $table->unsignedTinyInteger('warning_level')->default(2)->after('absences_remaining');
        });

        // Backfill existing courses from their stored attendance rate
        foreach (DB::table('courses')->get() as $course) {
            $used = (int) round((100 - $course->attendance_rate) / 100 * 30);
            $allowed = max(6, $used);

            DB::table('courses')
                ->where('id', $course->id)
                ->update([
                    'absences_allowed' => $allowed,
                    'absences_remaining' => $allowed - $used,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn([
                'total_sessions',
                'absences_allowed',
                'absences_remaining',
                'warning_level',
            ]);
        });
    }
};
