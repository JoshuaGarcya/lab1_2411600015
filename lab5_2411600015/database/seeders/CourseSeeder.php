<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [

            [
                'course_code' => 'CS201',
                'course_name' => 'Data Structures and Algorithms',
                'category' => 'Major',
                'units' => 3,
                'grade' => 3.75,
                'instructor' => 'Prof. Santos',
                'attendance_rate' => 96,
            ],

            [
                'course_code' => 'CS210',
                'course_name' => 'Database Management Systems',
                'category' => 'Major',
                'units' => 3,
                'grade' => 1.85,
                'instructor' => 'Prof. Reyes',
                'attendance_rate' => 78,
            ],

            [
                'course_code' => 'IT205',
                'course_name' => 'Web Systems and Technologies',
                'category' => 'Major',
                'units' => 3,
                'grade' => 3.25,
                'instructor' => 'Prof. Garcia',
                'attendance_rate' => 92,
            ],

            [
                'course_code' => 'IT220',
                'course_name' => 'System Analysis and Design',
                'category' => 'Major',
                'units' => 3,
                'grade' => 2.75,
                'instructor' => 'Prof. Cruz',
                'attendance_rate' => 88,
            ],

            [
                'course_code' => 'CS215',
                'course_name' => 'Object-Oriented Programming',
                'category' => 'Major',
                'units' => 3,
                'grade' => 3.50,
                'instructor' => 'Prof. Mendoza',
                'attendance_rate' => 94,
            ],

            [
                'course_code' => 'IT230',
                'course_name' => 'Information Management',
                'category' => 'Major',
                'units' => 3,
                'grade' => 2.25,
                'instructor' => 'Prof. Ramos',
                'attendance_rate' => 82,
            ],

            [
                'course_code' => 'GE101',
                'course_name' => 'Understanding the Self',
                'category' => 'Gen Ed',
                'units' => 3,
                'grade' => 3.00,
                'instructor' => 'Prof. Torres',
                'attendance_rate' => 90,
            ],

            [
                'course_code' => 'GE102',
                'course_name' => 'Readings in Philippine History',
                'category' => 'Gen Ed',
                'units' => 3,
                'grade' => 3.25,
                'instructor' => 'Prof. Navarro',
                'attendance_rate' => 95,
            ],

            [
                'course_code' => 'GE103',
                'course_name' => 'Purposive Communication',
                'category' => 'Gen Ed',
                'units' => 3,
                'grade' => 3.75,
                'instructor' => 'Prof. Flores',
                'attendance_rate' => 97,
            ],

            [
                'course_code' => 'PE101',
                'course_name' => 'Physical Education',
                'category' => 'Minor',
                'units' => 2,
                'grade' => 4.00,
                'instructor' => 'Prof. Castillo',
                'attendance_rate' => 98,
            ],

            // 5 additional courses

            [
                'course_code' => 'IT240',
                'course_name' => 'Computer Networks',
                'category' => 'Major',
                'units' => 3,
                'grade' => 2.50,
                'instructor' => 'Prof. Aquino',
                'attendance_rate' => 85,
            ],

            [
                'course_code' => 'CS225',
                'course_name' => 'Operating Systems',
                'category' => 'Major',
                'units' => 3,
                'grade' => 1.75,
                'instructor' => 'Prof. Villanueva',
                'attendance_rate' => 76,
            ],

            [
                'course_code' => 'GE104',
                'course_name' => 'Ethics',
                'category' => 'Gen Ed',
                'units' => 3,
                'grade' => 3.50,
                'instructor' => 'Prof. Bautista',
                'attendance_rate' => 93,
            ],

            [
                'course_code' => 'EL101',
                'course_name' => 'Introduction to Cloud Computing',
                'category' => 'Elective',
                'units' => 3,
                'grade' => 2.90,
                'instructor' => 'Prof. Fernandez',
                'attendance_rate' => 89,
            ],

            [
                'course_code' => 'EL102',
                'course_name' => 'Mobile Application Development',
                'category' => 'Elective',
                'units' => 3,
                'grade' => 3.85,
                'instructor' => 'Prof. Diaz',
                'attendance_rate' => 96,
            ],
        ];

        foreach ($courses as $course) {
            Course::create($course);
        }
    }
}