@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <!-- Dashboard Header -->
    <div class="d-flex justify-content-between align-items-center pb-2 mb-4 border-bottom">
        <div>
            <h2 class="mb-1">Dashboard</h2>
            <p class="text-muted mb-0">
    @php
        $hour = now()->hour;

        if ($hour < 12) {
            $greeting = 'Good Morning';
        } elseif ($hour < 18) {
            $greeting = 'Good Afternoon';
        } else {
            $greeting = 'Good Evening';
        }
    @endphp

    {{ $greeting }}, {{ Auth::user()->name }}!
</p>
        </div>

        <div class="text-end">
            <small class="text-muted d-block">
                Last updated: <span id="last-updated">just now</span>
            </small>
            <small id="live-status" class="text-success">● Live</small>
        </div>
    </div>


    <!-- Course Attention Alert -->
    @if($coursesNeedingAttention->count())

        <div class="alert alert-warning shadow-sm mb-4" role="alert">

            <div class="d-flex align-items-start">

                <div class="me-3 fs-4">
                    ⚠️
                </div>

                <div class="flex-grow-1">

                    <h5 class="alert-heading mb-1">
                        Courses Need Attention
                    </h5>

                    <p class="mb-2">
                        {{ $coursesNeedingAttention->count() }}
                        course(s) may need your attention.
                    </p>

                    <ul class="mb-0">

                        @foreach($coursesNeedingAttention as $course)

                            <li class="mb-1">

                                <strong>
                                    {{ $course->course_code }}
                                </strong>

                                — {{ $course->course_name }}

                                @if($course->grade < 3.0)

                                    <span class="text-danger ms-1">
                                        Grade: {{ number_format($course->grade, 2) }}
                                    </span>

                                @endif

                                @if($course->attendance_rate < 80)

                                    <span class="text-danger ms-1">
                                        Attendance: {{ $course->attendance_rate }}%
                                    </span>

                                @endif

                            </li>

                        @endforeach

                    </ul>

                </div>

            </div>

        </div>

    @endif


    <!-- Statistics -->
    <div class="row g-4 mb-4">

        <!-- GPA -->
        <div class="col-md-6 col-xl-3">

            <div class="card shadow-sm h-100 dashboard-stat-card">

                <div class="card-body">

                    <p class="text-muted stat-label">
                        GPA
                    </p>

                    <h2 id="stat1-value" class="stat-value mb-0">
                        {{ $averageGrade }}
                    </h2>

                </div>

            </div>

        </div>


        <!-- Courses -->
        <div class="col-md-6 col-xl-3">

            <div class="card shadow-sm h-100 dashboard-stat-card">

                <div class="card-body">

                    <p class="text-muted stat-label">
                        Courses
                    </p>

                    <h2 id="stat2-value" class="stat-value mb-0">
                        {{ $totalCourses }}
                    </h2>

                </div>

            </div>

        </div>


       <!-- At Risk -->
<div class="col-md-6 col-xl-3">
    <div class="card shadow-sm h-100 dashboard-stat-card">
        <div class="card-body">
            <p class="text-muted stat-label">
                At Risk
            </p>

            <h2 id="stat3-value" class="stat-value mb-0">
                {{ $atRiskCourses }}
            </h2>

            <small class="text-muted">
                Courses needing attention
            </small>
        </div>
    </div>
</div>


        <!-- Attendance -->
        <div class="col-md-6 col-xl-3">

            <div class="card shadow-sm h-100 dashboard-stat-card">

                <div class="card-body">

                    <p class="text-muted stat-label">
                        Attendance
                    </p>

                    <h2 id="stat4-value" class="stat-value mb-0">
                        {{ $averageAttendance }}%
                    </h2>

                </div>

            </div>

        </div>

    </div>


    <!-- Charts -->
    <div class="row g-4 mb-4">

        <!-- Category Chart -->
        <div class="col-lg-6">

            <div class="card shadow-sm h-100">

                <div class="card-header">
                    <h5 class="mb-0">
                        Units by Course Category
                    </h5>
                </div>

                <div class="card-body">

                    <div style="height: 300px;">
                        <canvas id="categoryChart"></canvas>
                    </div>

                </div>

            </div>

        </div>


        <!-- Status Chart -->
        <div class="col-lg-6">

            <div class="card shadow-sm h-100">

                <div class="card-header">
                    <h5 class="mb-0">
                        Course Status Distribution
                    </h5>
                </div>

                <div class="card-body">

                    <div style="height: 300px;">
                        <canvas id="statusChart"></canvas>
                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- Top Courses -->
    <div class="card shadow-sm mb-4">

        <div class="card-header">
            <h5 class="mb-0">
                Top Courses by Grade
            </h5>
        </div>

        <div class="card-body">

            <div style="height: 350px;">
                <canvas id="topCoursesChart"></canvas>
            </div>

        </div>

    </div>


    <!-- Attendance by Course -->
    <div class="card shadow-sm mb-4">

        <div class="card-header">
            <h5 class="mb-0">
                Attendance by Course
            </h5>
        </div>

        <div class="card-body">

            <div style="height: 320px;">
                <canvas id="attendanceChart"></canvas>
            </div>

        </div>

    </div>


    <!-- Live allowance monitor -->
    <div id="live-config"
         data-stats-url="{{ route('api.dashboard.stats') }}"
         data-low-url="{{ route('api.courses.low-allowance') }}"
         data-recent-url="{{ route('api.transactions.recent') }}">
    </div>

    <div class="row g-4 mb-4">

        <!-- Low allowance alerts -->
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Low Allowance Alerts</h5>
                    <a href="{{ route('reports.index', ['report' => 'low']) }}" class="btn btn-sm btn-primary">Report</a>
                </div>

                <div class="card-body">
                    <p id="low-allowance-empty" class="text-muted mb-0 d-none">
                        No courses are near their absence limit.
                    </p>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th class="text-end">Left</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="low-allowance-body">
                                <tr><td colspan="3" class="text-muted">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent transactions -->
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Transactions</h5>
                    <a href="{{ route('attendance-transactions.index') }}" class="btn btn-sm btn-primary">View All</a>
                </div>

                <div class="card-body">
                    <p id="recent-empty" class="text-muted mb-0 d-none">
                        No attendance transactions yet.
                    </p>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>When</th>
                                    <th>Course</th>
                                    <th>Type</th>
                                    <th class="text-end">Qty</th>
                                    <th>By</th>
                                </tr>
                            </thead>
                            <tbody id="recent-body">
                                <tr><td colspan="5" class="text-muted">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>


    <!-- Recent Courses -->
    <div class="card shadow-sm">

        <div class="card-header d-flex justify-content-between align-items-center">

            <h5 class="mb-0">
                Recent Courses
            </h5>

            <a href="{{ route('courses.index') }}"
               class="btn btn-sm btn-primary">
                View All
            </a>

        </div>


        <div class="card-body">

            @if($recentCourses->count())

                <div class="table-responsive">

                    <table class="table table-striped table-hover align-middle">

                        <thead>

                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Category</th>
                                <th>Instructor</th>
                                <th>Grade</th>
                                <th>Status</th>
                            </tr>

                        </thead>


                        <tbody>

                            @foreach($recentCourses as $course)

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
                                        {{ $course->instructor }}
                                    </td>

                                    <td>
                                        {{ number_format($course->grade, 2) }}
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

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @else

                <p class="text-muted mb-0">
                    No courses have been added yet.
                </p>

            @endif

        </div>

    </div>

</div>


<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="{{ asset('js/charts.js') }}"></script>
<script src="{{ asset('js/dashboard-live.js') }}"></script>


<script>

    const categoryData = @json($categorySummary);
    
    const statusData = @json($statusStats);

    const coursesData = @json($recentCourses);

    const attendanceData = @json($attendanceByCourse);


    dashboardCharts.renderAll(
        categoryData,
        statusData,
        coursesData,
        attendanceData
    );

    // Start polling the JSON endpoints every 30 seconds
    dashboardLive.start(30000);

</script>

@endsection