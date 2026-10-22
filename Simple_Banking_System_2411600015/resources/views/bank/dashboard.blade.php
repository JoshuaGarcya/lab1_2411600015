@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="pb-2 mb-4 border-bottom">
        <h2 class="mb-1">Bank Dashboard</h2>
        <p class="text-muted mb-0">The numbers refresh by themselves every 30 seconds.</p>
    </div>

    @include('partials.flash')

    {{-- Filled in by JavaScript when a request fails --}}
    <div id="stats-error" class="alert alert-danger d-none" role="alert"></div>

    <table class="table table-bordered w-auto">
        <tbody>
            <tr>
                <th>Total accounts</th>
                <td id="total_accounts">Loading…</td>
            </tr>
            <tr>
                <th>Total money</th>
                <td id="total_money">Loading…</td>
            </tr>
            <tr>
                <th>Transactions today</th>
                <td id="total_transactions_today">Loading…</td>
            </tr>
        </tbody>
    </table>

    <p class="text-muted small">Last updated: <span id="last-updated">never</span></p>

</div>

@endsection

@push('scripts')
<script>
    (function () {
        const statsUrl = '{{ route('api.dashboard.stats', [], false) }}';
        const errorBox = document.getElementById('stats-error');

        function showError(message) {
            errorBox.textContent = message + ' The old numbers stay on screen. Trying again in 30 seconds.';
            errorBox.classList.remove('d-none');
        }

        function loadStats() {
            fetch(statsUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
                .then(function (response) {
                    if (response.status === 401) {
                        throw new Error('Your session has expired. Please log in again.');
                    }
                    if (!response.ok) {
                        throw new Error('The server returned an error (' + response.status + ').');
                    }
                    return response.json();
                })
                .then(function (stats) {
                    document.getElementById('total_accounts').textContent = stats.total_accounts;
                    document.getElementById('total_money').textContent =
                        Number(stats.total_money).toLocaleString('en-PH', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    document.getElementById('total_transactions_today').textContent = stats.total_transactions_today;
                    document.getElementById('last-updated').textContent = new Date().toLocaleTimeString();
                    errorBox.classList.add('d-none');
                })
                .catch(function (error) {
                    // fetch() rejects with a TypeError when the network is down
                    if (error instanceof TypeError) {
                        showError('Network problem: could not reach the server.');
                    } else {
                        showError(error.message);
                    }
                });
        }

        loadStats();                      // fetch right away
        setInterval(loadStats, 30000);    // then every 30 seconds
    })();
</script>
@endpush
