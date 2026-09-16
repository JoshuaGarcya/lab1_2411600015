@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center pb-2 mb-4 border-bottom">
        <div>
            <h2 class="mb-1">Add Course</h2>
            <p class="text-muted mb-0">
                Add a new course to your student portal.
            </p>
        </div>

        <a href="{{ route('courses.index') }}" class="btn btn-secondary">
            Back to Courses
        </a>
    </div>


    <div class="card shadow-sm course-form-card">

        <div class="card-header">
            <h5 class="mb-0">Course Information</h5>
        </div>

        <div class="card-body">

            <form method="POST" action="{{ route('courses.store') }}">

                @include('courses._form', [
                    'buttonText' => 'Add Course'
                ])

            </form>

        </div>

    </div>

</div>

@endsection