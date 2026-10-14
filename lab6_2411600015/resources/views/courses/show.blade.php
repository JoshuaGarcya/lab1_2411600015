@extends('layouts.app')

@section('content')

<div class="container">

    <h1>{{ $course->course_name }}</h1>

    @include('partials.flash')

    <p><strong>Course Code:</strong> {{ $course->course_code }}</p>

    <p><strong>Category:</strong> {{ $course->category }}</p>

    <p><strong>Units:</strong> {{ $course->units }}</p>

    <p><strong>Grade:</strong> {{ number_format($course->grade, 2) }}</p>

    <p><strong>Status:</strong> {{ $course->status }}</p>

    <p><strong>Quality Points:</strong> {{ $course->quality_points }}</p>

    <p><strong>Instructor:</strong> {{ $course->instructor }}</p>

    <p><strong>Attendance:</strong> {{ $course->attendance_rate }}%</p>

    <p>
        <strong>Absence Allowance:</strong>
        {{ $course->absences_remaining }} of {{ $course->absences_allowed }} remaining
        <span class="badge bg-{{ $course->allowance_status_color }} {{ $course->allowance_status_color === 'warning' ? 'text-dark' : '' }}">
            {{ $course->allowance_status }}
        </span>
    </p>

    <a href="{{ route('courses.edit', $course) }}" class="btn btn-primary">
        Edit
    </a>

    <a href="{{ route('attendance-transactions.create', ['course_id' => $course->id]) }}" class="btn btn-outline-primary">
        Record Transaction
    </a>

    <a href="{{ route('courses.index') }}" class="btn btn-secondary">
        Back to Courses
    </a>


    <!-- Transaction history with running balance -->
    <div class="card shadow-sm mt-4">

        <div class="card-header">
            <h5 class="mb-0">Attendance History</h5>
        </div>

        <div class="card-body">

            <form method="GET" action="{{ route('courses.show', $course) }}" class="mb-3">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="date_from" class="form-label">From</label>
                        <input type="date" id="date_from" name="date_from" class="form-control"
                               value="{{ request('date_from') }}">
                    </div>

                    <div class="col-md-3">
                        <label for="date_to" class="form-label">To</label>
                        <input type="date" id="date_to" name="date_to" class="form-control"
                               value="{{ request('date_to') }}">
                    </div>

                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('courses.show', $course) }}" class="btn btn-secondary">Reset</a>
                    </div>
                </div>

                @if($errors->any())
                    <div class="text-danger small mt-2">{{ $errors->first() }}</div>
                @endif
            </form>

            @if($transactions->count())

                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Date / Time</th>
                                <th>Type</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Balance After</th>
                                <th>Reference</th>
                                <th>Recorded By</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($transactions as $transaction)
                                <tr>
                                    <td>
                                        <a href="{{ route('attendance-transactions.show', $transaction) }}">
                                            {{ $transaction->created_at->format('M d, Y h:i A') }}
                                        </a>
                                    </td>

                                    <td>
                                        <span class="badge bg-{{ $transaction->type_badge }} {{ $transaction->type_badge === 'info' ? 'text-dark' : '' }}">
                                            {{ $transaction->type_label }}
                                        </span>
                                    </td>

                                    <td class="text-end fw-bold {{ $transaction->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $transaction->signed_quantity }}
                                    </td>

                                    <td class="text-end">{{ $transaction->running_balance }}</td>

                                    <td>{{ $transaction->reference_number ?? '—' }}</td>

                                    <td>{{ $transaction->user->name ?? 'Deleted user' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @else
                <p class="text-muted mb-0">No transactions recorded for this course yet.</p>
            @endif

        </div>

    </div>

</div>

@endsection
