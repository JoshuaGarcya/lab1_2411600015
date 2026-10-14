<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>GSCSDA Student Portal</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Your Lab 4 CSS -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">

            <a class="navbar-brand fw-bold" href="{{ route('courses.index') }}">
                GSCSDA Student Portal
            </a>

            <button class="navbar-toggler"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">

                    @php
                        $unreadNotifications = Auth::user()->unreadNotifications;
                    @endphp

                    <!-- Notification bell -->
                    <li class="nav-item dropdown me-3">
                        <a class="nav-link position-relative text-white"
                           href="#"
                           id="notificationBell"
                           role="button"
                           data-bs-toggle="dropdown"
                           aria-expanded="false"
                           aria-label="Notifications">
                            &#128276;
                            <span id="bell-count"
                                  class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle {{ $unreadNotifications->count() ? '' : 'd-none' }}">
                                {{ $unreadNotifications->count() }}
                            </span>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end p-0 shadow"
                            style="min-width: 340px;"
                            aria-labelledby="notificationBell">

                            <li class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                <strong>Notifications</strong>

                                @if($unreadNotifications->count())
                                    <form method="POST" action="{{ route('notifications.readAll') }}">
                                        @csrf
                                        <button type="submit" class="btn btn-link btn-sm p-0">
                                            Mark all as read
                                        </button>
                                    </form>
                                @endif
                            </li>

                            @forelse($unreadNotifications->take(5) as $notification)
                                <li>
                                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-wrap py-2 border-bottom">
                                            <div class="fw-semibold">
                                                {{ $notification->data['course_code'] ?? 'Course' }}
                                                @if(!empty($notification->data['limit_reached']))
                                                    <span class="badge bg-danger ms-1">Limit reached</span>
                                                @else
                                                    <span class="badge bg-warning text-dark ms-1">Low allowance</span>
                                                @endif
                                            </div>
                                            <div class="small text-muted">
                                                {{ $notification->data['message'] ?? '' }}
                                            </div>
                                            <div class="small text-muted">
                                                {{ $notification->created_at->diffForHumans() }}
                                            </div>
                                        </button>
                                    </form>
                                </li>
                            @empty
                                <li class="px-3 py-3 text-muted small">
                                    You're all caught up.
                                </li>
                            @endforelse

                        </ul>
                    </li>

                    <!-- User dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white"
                           href="#"
                           id="userMenu"
                           role="button"
                           data-bs-toggle="dropdown"
                           aria-expanded="false">
                            Welcome, {{ Auth::user()->name }}
                            @if(Auth::user()->is_admin)
                                <span class="badge bg-light text-primary ms-1">Admin</span>
                            @endif
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </li>

                </ul>
            </div>
        </div>
    </nav>


    <!-- Main Layout -->
    <div class="container-fluid">
        <div class="row">

            <!-- Sidebar -->
           <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar vh-100 p-3">
    <div class="position-sticky">

        <ul class="nav flex-column">

            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('dashboard') }}">
                    Dashboard
                </a>
            </li>

            <!-- My Courses -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('courses.index') }}">
                    My Courses & Grades
                </a>
            </li>

            <!-- Add Course -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('courses.create') }}">
                    Add Course
                </a>
            </li>

            <!-- Attendance Log -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('attendance-transactions.index') }}">
                    Attendance Log
                </a>
            </li>

            @if(Auth::user()->is_admin)
                <!-- Adjust Count (admin) -->
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('adjustments.create') }}">
                        Adjust Count
                    </a>
                </li>
            @endif

            <!-- Reports -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('reports.index') }}">
                    Reports
                </a>
            </li>

            <!-- Logout -->
            <li class="nav-item">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button
                        type="submit"
                        class="nav-link text-danger border-0 bg-transparent"
                    >
                        Logout
                    </button>
                </form>
            </li>

        </ul>

    </div>
</nav>


            <!-- Page Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">

                @yield('content')

            </main>

        </div>
    </div>


    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')

</body>

</html>