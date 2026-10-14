@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center pb-2 mb-4 border-bottom">
        <div>
            <h2 class="mb-1">Attendance Log</h2>
            <p class="text-muted mb-0">
                Every absence, restored allowance and adjustment, with who recorded it.
            </p>
        </div>

        <div class="d-flex gap-2">
            @if(Auth::user()->is_admin)
                <a href="{{ route('adjustments.create') }}" class="btn btn-outline-primary">
                    Adjust Count
                </a>
            @endif

            <a href="{{ route('attendance-transactions.create') }}" class="btn btn-primary">
                + Record Transaction
            </a>
        </div>
    </div>

    @include('partials.flash')

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('attendance-transactions.index') }}">
                <div class="row g-3 align-items-end">

                    <div class="col-md-3">
                        <label for="course_id" class="form-label">Course</label>
                        <select id="course_id" name="course_id" class="form-select">
                            <option value="">All Courses</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>
                                    {{ $course->course_code }} — {{ $course->course_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="type" class="form-label">Type</label>
                        <select id="type" name="type" class="form-select">
                            <option value="">All Types</option>
                            @foreach($types as $type)
                                <option value="{{ $type }}" @selected(request('type') === $type)>
                                    {{ ucfirst($type) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="date_from" class="form-label">From</label>
                        <input type="date" id="date_from" name="date_from" class="form-control"
                               value="{{ request('date_from') }}">
                    </div>

                    <div class="col-md-2">
                        <label for="date_to" class="form-label">To</label>
                        <input type="date" id="date_to" name="date_to" class="form-control"
                               value="{{ request('date_to') }}">
                    </div>

                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('attendance-transactions.index') }}" class="btn btn-secondary">Reset</a>
                    </div>

                </div>

                @if($errors->any())
                    <div class="text-danger small mt-2">{{ $errors->first() }}</div>
                @endif
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card shadow-sm">
        <div class="card-body">

            @if($transactions->count())

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Date / Time</th>
                                <th>Course</th>
                                <th>Type</th>
                                <th class="text-end">Quantity</th>
                                <th>Reference</th>
                                <th>Recorded By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($transactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->created_at->format('M d, Y h:i A') }}</td>

                                    <td>
                                        @if($transaction->course)
                                            <a href="{{ route('courses.show', $transaction->course) }}">
                                                {{ $transaction->course->course_code }}
                                            </a>
                                            <div class="small text-muted">{{ $transaction->course->course_name }}</div>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="badge bg-{{ $transaction->type_badge }} {{ $transaction->type_badge === 'info' ? 'text-dark' : '' }}">
                                            {{ $transaction->type_label }}
                                        </span>
                                    </td>

                                    <td class="text-end fw-bold {{ $transaction->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $transaction->signed_quantity }}
                                    </td>

                                    <td>{{ $transaction->reference_number ?? '—' }}</td>

                                    <td>{{ $transaction->user->name ?? 'Deleted user' }}</td>

                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('attendance-transactions.show', $transaction) }}"
                                               class="btn btn-sm btn-outline-primary">View</a>

                                            @if(Auth::user()->is_admin)
                                                <form method="POST"
                                                      action="{{ route('attendance-transactions.destroy', $transaction) }}"
                                                      onsubmit="return confirm('Delete this transaction? Its effect on the course will be reversed.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $transactions->links('pagination::bootstrap-5') }}
                </div>

            @else
                <p class="text-muted mb-0">No transactions match your filters.</p>
            @endif

        </div>
    </div>

</div>

@endsection
