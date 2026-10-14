<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>GSCSDAES Student Portal - Login</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body class="login-page">

    <div class="container d-flex justify-content-center align-items-center min-vh-100">

        <div class="card shadow-lg login-card">

            <!-- Login Header -->
            <div class="card-header bg-primary text-white text-center py-4">

                <h4 class="mb-1">
                    GSCSDAES STUDENT PORTAL
                </h4>

                <p class="mb-0">
                    Login to your dashboard
                </p>

            </div>


            <!-- Login Form -->
            <div class="card-body p-4">

                @if(session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">

                    @csrf

                    <!-- Email -->
                    <div class="mb-3">

                        <label for="email" class="form-label">
                            Email
                        </label>

                        <div class="input-group">

                            <span class="input-group-text">
                                👤
                            </span>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control"
                                value="{{ old('email') }}"
                                placeholder="Enter email"
                                required
                                autofocus
                            >

                        </div>

                    </div>


                    <!-- Password -->
                    <div class="mb-3">

                        <label for="password" class="form-label">
                            Password
                        </label>

                        <div class="input-group">

                            <span class="input-group-text">
                                🔒
                            </span>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control"
                                placeholder="Enter password"
                                required
                            >

                        </div>

                    </div>


                    <!-- Remember Me -->
                    <div class="form-check mb-3">
    <input
        type="checkbox"
        id="remember"
        name="remember"
        class="form-check-input"
        value="1"
    >

    <label for="remember" class="form-check-label">
        Remember Me
    </label>
</div>


                    <!-- Login Button -->
                    <button
    type="submit"
    class="btn btn-primary w-100 py-2"
>
    Login
</button>

<div class="text-center mt-3">

    <p class="mb-2">
        Don't have an account?
        <a href="{{ route('register') }}">
            Register here
        </a>
    </p>

    <a href="{{ route('password.request') }}">
        Forgot your password?
    </a>

</div>

        </div>

    </div>

</body>
</html>