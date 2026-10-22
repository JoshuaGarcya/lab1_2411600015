@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center pb-2 mb-4 border-bottom">
        <div>
            <h2 class="mb-1">Transactions</h2>
            <p class="text-muted mb-0">Deposits and withdrawals.</p>
        </div>

        <a href="{{ route('transactions.create') }}" class="btn btn-primary">+ New Transaction</a>
    </div>

    @include('partials.flash')

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Date</th>
                <th>Account</th>
                <th>Type</th>
                <th class="text-end">Amount</th>
                <th>Reference</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $transaction)
                <tr>
                    <td>{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $transaction->account->account_number }}</td>
                    <td>{{ ucfirst($transaction->type) }}</td>
                    <td class="text-end">{{ number_format($transaction->amount, 2) }}</td>
                    <td>{{ $transaction->reference_number }}</td>
                    <td>{{ $transaction->notes }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">No transactions yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $transactions->links('pagination::bootstrap-5') }}

</div>

@endsection
