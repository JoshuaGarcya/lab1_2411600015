<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Simple Banking System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand navbar-dark bg-primary px-3">
    <a class="navbar-brand" href="{{ route('bank.dashboard') }}">Simple Banking System</a>

    @auth
        <ul class="navbar-nav me-auto">
            <li class="nav-item"><a class="nav-link" href="{{ route('bank.dashboard') }}">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ route('transactions.index') }}">Transactions</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ route('transactions.create') }}">New Transaction</a></li>
        </ul>

        @php $unread = Auth::user()->unreadNotifications()->count(); @endphp

        <span class="navbar-text me-3">
            {{ Auth::user()->name }}@if(Auth::user()->isAdmin()) (admin)@endif
        </span>

        <a class="nav-link text-white me-3" href="{{ route('notifications.index') }}">
            Notifications
            @if($unread > 0)
                <span class="badge bg-danger">{{ $unread }}</span>
            @endif
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-sm btn-light">Logout</button>
        </form>
    @endauth
</nav>

<main class="container py-4">
    @yield('content')
</main>

@stack('scripts')

</body>
</html>
