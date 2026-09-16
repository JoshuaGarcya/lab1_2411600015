@extends('layouts.app')

@section('content')

<div class="container">

    <h1>{{ $course->course_name }}</h1>

    <p><strong>Course Code:</strong> {{ $course->course_code }}</p>

    <p><strong>Category:</strong> {{ $course->category }}</p>

    <p><strong>Units:</strong> {{ $course->units }}</p>

    <p><strong>Grade:</strong> {{ number_format($course->grade, 2) }}</p>

    <p><strong>Status:</strong> {{ $course->status }}</p>

    <p><strong>Quality Points:</strong> {{ $course->quality_points }}</p>

    <p><strong>Instructor:</strong> {{ $course->instructor }}</p>

    <p><strong>Attendance:</strong> {{ $course->attendance_rate }}%</p>

    <a href="{{ route('courses.edit', $course) }}" class="btn btn-primary">
        Edit
    </a>

    <a href="{{ route('courses.index') }}" class="btn btn-secondary">
        Back to Courses
    </a>

</div>

@endsection