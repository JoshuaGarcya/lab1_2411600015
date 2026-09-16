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

                    <li class="nav-item">
                        <span class="navbar-text text-white me-3">
                            Welcome, {{ Auth::user()->name }}
                        </span>
                    </li>

                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <button type="submit" class="btn btn-outline-light btn-sm">
                                Logout
                            </button>
                        </form>
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

</body>

</html>