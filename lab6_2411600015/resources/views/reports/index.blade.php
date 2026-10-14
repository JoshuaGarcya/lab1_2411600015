@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center pb-2 mb-4 border-bottom">
        <div>
            <h2 class="mb-1">Attendance Reports</h2>
            <p class="text-muted mb-0">{{ $data['title'] }}</p>
        </div>

        <a href="{{ route('reports.export', ['report' => $report, 'from' => $from, 'to' => $to]) }}"
           class="btn btn-outline-primary">
            Export CSV
        </a>
    </div>

    <!-- Report tabs -->
    <ul class="nav nav-tabs mb-4">
        @foreach($reports as $key => $label)
            <li class="nav-item">
                <a class="nav-link {{ $report === $key ? 'active' : '' }}"
                   href="{{ route('reports.index', ['report' => $key, 'from' => $from, 'to' => $to]) }}">
                    {{ $label }}
                </a>
            </li>
        @endforeach
    </ul>

    <!-- Date range (movement report only) -->
    @if($report === 'movement')
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('reports.index') }}">
                    <input type="hidden" name="report" value="movement">

                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="from" class="form-label">From</label>
                            <input type="date" id="from" name="from" class="form-control" value="{{ $from }}">
                        </div>

                        <div class="col-md-3">
                            <label for="to" class="form-label">To</label>
                            <input type="date" id="to" name="to" class="form-control" value="{{ $to }}">
                        </div>

                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">Generate</button>
                        </div>
                    </div>

                    @if($errors->any())
                        <div class="text-danger small mt-2">{{ $errors->first() }}</div>
                    @endif
                </form>
            </div>
        </div>
    @endif

    <!-- Summary -->
    <div class="row g-3 mb-4">
        @foreach($data['summary'] as $label => $value)
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <p class="text-muted stat-label mb-1">{{ $label }}</p>
                        <h3 class="stat-value mb-0">{{ $value }}</h3>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Table -->
    <div class="card shadow-sm">
        <div class="card-body">

            @if(count($data['rows']))
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                @foreach($data['headings'] as $heading)
                                    <th>{{ $heading }}</th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($data['rows'] as $row)
                                <tr>
                                    @foreach($row as $cell)
                                        <td>{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted mb-0">Nothing to report for this selection.</p>
            @endif

        </div>
    </div>

</div>

@endsection
