@extends('layouts.app')

@section('content')

<div class="container">

    <h1>Edit Course</h1>

    <form method="POST" action="{{ route('courses.update', $course) }}">

        @method('PUT')

        @include('courses._form', [
            'course' => $course,
            'buttonText' => 'Update Course'
        ])

    </form>

</div>

@endsection