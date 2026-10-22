@extends('layouts.app')

@section('content')

<div class="container" style="max-width: 760px;">

    <div class="pb-2 mb-4 border-bottom">
        <h2 class="mb-1">New Transaction</h2>
        <p class="text-muted mb-0">Make a deposit or a withdrawal.</p>
    </div>

    @include('partials.flash')

    @if($accounts->isEmpty())
        <div class="alert alert-warning">
            You do not have a bank account yet.
        </div>
    @else
        <form method="POST" action="{{ route('transactions.store') }}">
            @csrf

            <div class="mb-3">
                <label for="account_id" class="form-label">Account</label>
                <select id="account_id" name="account_id" class="form-select" required>
                    <option value="">Select an account…</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" @selected(old('account_id') == $account->id)>
                            {{ $account->account_number }}
                            — balance {{ number_format($account->balance, 2) }}
                            (minimum {{ number_format($account->minimum_balance, 2) }})
                        </option>
                    @endforeach
                </select>
                @error('account_id')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="type" class="form-label">Type</label>
                <select id="type" name="type" class="form-select" required>
                    <option value="deposit" @selected(old('type', 'deposit') === 'deposit')>Deposit</option>
                    <option value="withdrawal" @selected(old('type') === 'withdrawal')>Withdrawal</option>
                </select>
                @error('type')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="amount" class="form-label">Amount</label>
                <input type="number" id="amount" name="amount" class="form-control"
                       min="1" step="0.01" value="{{ old('amount') }}" required>
                @error('amount')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="notes" class="form-label">Notes (optional)</label>
                <textarea id="notes" name="notes" rows="3" maxlength="500"
                          class="form-control">{{ old('notes') }}</textarea>
                @error('notes')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Transaction</button>
                <a href="{{ route('transactions.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    @endif

</div>

@endsection
