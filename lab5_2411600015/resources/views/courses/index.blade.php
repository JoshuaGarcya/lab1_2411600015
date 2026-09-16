@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center pb-2 mb-4 border-bottom">

        <div>
            <h2 class="mb-1">My Courses & Grades</h2>

            <p class="text-muted mb-0">
                View and manage your courses and grades.
            </p>
        </div>

        <a href="{{ route('courses.create') }}" class="btn btn-primary">
            + Add Course
        </a>

    </div>


    <!-- Filters -->
    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <form method="GET" action="{{ route('courses.index') }}">

                <div class="row g-3 align-items-end">

                    <!-- Category -->
                    <div class="col-md-4">

                        <label for="category" class="form-label">
                            Category
                        </label>

                        <select
                            id="category"
                            name="category"
                            class="form-select"
                        >

                            <option value="">
                                All Categories
                            </option>

                            <option
                                value="Major"
                                @selected(request('category') === 'Major')
                            >
                                Major
                            </option>

                            <option
                                value="Minor"
                                @selected(request('category') === 'Minor')
                            >
                                Minor
                            </option>

                            <option
                                value="Gen Ed"
                                @selected(request('category') === 'Gen Ed')
                            >
                                Gen Ed
                            </option>

                            <option
                                value="Elective"
                                @selected(request('category') === 'Elective')
                            >
                                Elective
                            </option>

                        </select>

                    </div>


                    <!-- Search -->
                    <div class="col-md-5">

                        <label for="search" class="form-label">
                            Search
                        </label>

                        <input
                            type="text"
                            id="search"
                            name="search"
                            class="form-control"
                            value="{{ request('search') }}"
                            placeholder="Search by name, code, or instructor..."
                        >

                    </div>


                    <!-- Apply -->
                    <div class="col-md-3">

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            Apply
                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>


    <!-- Course Table -->
    <div class="card shadow-sm">

        <div class="card-header d-flex justify-content-between align-items-center">

            <h5 class="mb-0">
                Courses
            </h5>

            <span class="text-muted">
                {{ $courses->count() }} course(s)
            </span>

        </div>


        <div class="card-body">

            @if($courses->count())

                <div class="table-responsive">

                    <table class="table table-striped table-hover align-middle">

                        <thead>

                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Category</th>
                                <th>Units</th>
                                <th>Instructor</th>
                                <th>Grade</th>
                                <th>Attendance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>

                        </thead>


                        <tbody>

                            @foreach($courses as $course)

                                <tr>

                                    <td>
                                        {{ $course->course_code }}
                                    </td>

                                    <td>
                                        {{ $course->course_name }}
                                    </td>

                                    <td>
                                        {{ $course->category }}
                                    </td>

                                    <td>
                                        {{ $course->units }}
                                    </td>

                                    <td>
                                        {{ $course->instructor }}
                                    </td>

                                    <td>
                                        {{ number_format($course->grade, 2) }}
                                    </td>

                                    <td>
                                        {{ $course->attendance_rate }}%
                                    </td>

                                    <td>

                                        @if($course->status === 'Passing')

                                            <span class="badge bg-success">
                                                Passing
                                            </span>

                                        @elseif($course->status === 'At Risk')

                                            <span class="badge bg-warning text-dark">
                                                At Risk
                                            </span>

                                        @else

                                            <span class="badge bg-danger">
                                                Failing
                                            </span>

                                        @endif

                                    </td>


                                    <td>

                                        <div class="d-flex gap-1">

                                            <a
                                                href="{{ route('courses.show', $course) }}"
                                                class="btn btn-sm btn-info"
                                            >
                                                View
                                            </a>

                                            <a
                                                href="{{ route('courses.edit', $course) }}"
                                                class="btn btn-sm btn-primary"
                                            >
                                                Edit
                                            </a>

                                            <form
                                                action="{{ route('courses.destroy', $course) }}"
                                                method="POST"
                                            >

                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Delete this course?')"
                                                >
                                                    Delete
                                                </button>

                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @else

                <div class="text-center py-5">

                    <h5>No courses found</h5>

                    <p class="text-muted">
                        Try changing your search or category filter.
                    </p>

                    <a
                        href="{{ route('courses.index') }}"
                        class="btn btn-secondary"
                    >
                        Clear Filters
                    </a>

                </div>

            @endif

        </div>

    </div>

</div>

@endsection