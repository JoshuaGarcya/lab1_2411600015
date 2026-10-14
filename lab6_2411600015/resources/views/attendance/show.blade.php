@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center pb-2 mb-4 border-bottom">
        <div>
            <h2 class="mb-1">Transaction #{{ $transaction->id }}</h2>
            <p class="text-muted mb-0">{{ $transaction->created_at->format('F d, Y h:i A') }}</p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('attendance-transactions.index') }}" class="btn btn-secondary">Back to Log</a>

            @if(Auth::user()->is_admin)
                <form method="POST"
                      action="{{ route('attendance-transactions.destroy', $transaction) }}"
                      onsubmit="return confirm('Delete this transaction? Its effect on the course will be reversed.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            @endif
        </div>
    </div>

    @include('partials.flash')

    <div class="row g-4">

        <!-- Transaction details -->
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header"><h5 class="mb-0">Transaction</h5></div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Type:</strong>
                        <span class="badge bg-{{ $transaction->type_badge }} {{ $transaction->type_badge === 'info' ? 'text-dark' : '' }}">
                            {{ $transaction->type_label }}
                        </span>
                    </p>
                    <p class="mb-2">
                        <strong>Quantity:</strong>
                        <span class="fw-bold {{ $transaction->quantity > 0 ? 'text-success' : 'text-danger' }}">
                            {{ $transaction->signed_quantity }}
                        </span>
                    </p>
                    <p class="mb-2"><strong>Reference:</strong> {{ $transaction->reference_number ?? '—' }}</p>
                    <p class="mb-2"><strong>Reason:</strong> {{ $transaction->reason ?? '—' }}</p>
                    <p class="mb-0"><strong>Notes:</strong> {{ $transaction->notes ?? '—' }}</p>
                </div>
            </div>
        </div>

        <!-- Course -->
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header"><h5 class="mb-0">Course</h5></div>
                <div class="card-body">
                    @if($transaction->course)
                        <p class="mb-2"><strong>Code:</strong> {{ $transaction->course->course_code }}</p>
                        <p class="mb-2"><strong>Name:</strong> {{ $transaction->course->course_name }}</p>
                        <p class="mb-2"><strong>Instructor:</strong> {{ $transaction->course->instructor }}</p>
                        <p class="mb-2">
                            <strong>Absences remaining now:</strong>
                            {{ $transaction->course->absences_remaining }} of {{ $transaction->course->absences_allowed }}
                        </p>
                        <p class="mb-3">
                            <strong>Status:</strong>
                            <span class="badge bg-{{ $transaction->course->allowance_status_color }} {{ $transaction->course->allowance_status_color === 'warning' ? 'text-dark' : '' }}">
                                {{ $transaction->course->allowance_status }}
                            </span>
                        </p>
                        <a href="{{ route('courses.show', $transaction->course) }}" class="btn btn-sm btn-primary">
                            View Course
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- User -->
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header"><h5 class="mb-0">Recorded By</h5></div>
                <div class="card-body">
                    @if($transaction->user)
                        <p class="mb-2"><strong>Name:</strong> {{ $transaction->user->name }}</p>
                        <p class="mb-0"><strong>Email:</strong> {{ $transaction->user->email }}</p>
                    @else
                        <p class="text-muted mb-0">This user account has been deleted.</p>
                    @endif
                </div>
            </div>
        </div>

    </div>

</div>

@endsection
