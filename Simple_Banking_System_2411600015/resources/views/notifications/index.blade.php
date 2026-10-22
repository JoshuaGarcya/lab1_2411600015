@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-4 border-bottom">
    <h2 class="mb-0">Notifications</h2>

    <form method="POST" action="{{ route('notifications.readAll') }}">
        @csrf
        <button type="submit" class="btn btn-outline-primary btn-sm">Mark all as read</button>
    </form>
</div>

@include('partials.flash')

<ul class="list-group mb-3">
    @forelse($notifications as $notification)
        <li class="list-group-item {{ $notification->read_at ? '' : 'list-group-item-warning' }}">
            <strong>Low balance</strong>
            @unless($notification->read_at)
                <span class="badge bg-danger">new</span>
            @endunless
            <div>{{ $notification->data['message'] ?? '' }}</div>
            <div class="small text-muted">{{ $notification->created_at->format('Y-m-d H:i') }}</div>
        </li>
    @empty
        <li class="list-group-item text-muted">No notifications.</li>
    @endforelse
</ul>

{{ $notifications->links('pagination::bootstrap-5') }}

@endsection
