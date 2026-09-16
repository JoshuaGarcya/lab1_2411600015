<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>GSCSDA Student Portal - Register</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body class="login-page">

    <div class="container d-flex justify-content-center align-items-center min-vh-100">

        <div class="card shadow-lg login-card">

            <div class="card-header bg-primary text-white text-center py-4">

                <h4 class="mb-1">
                    CREATE ACCOUNT
                </h4>

                <p class="mb-0">
                    Register for the Student Portal
                </p>

            </div>

            <div class="card-body p-4">

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}">

                    @csrf

                    <div class="mb-3">

                        <label for="name" class="form-label">
                            Full Name
                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="form-control"
                            value="{{ old('name') }}"
                            placeholder="Enter your full name"
                            required
                            autofocus
                        >

                    </div>

                    <div class="mb-3">

                        <label for="email" class="form-label">
                            Email
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            value="{{ old('email') }}"
                            placeholder="Enter your email"
                            required
                        >

                    </div>

                    <div class="mb-3">

                        <label for="password" class="form-label">
                            Password
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Create a password"
                            required
                        >

                    </div>

                    <div class="mb-3">

                        <label for="password_confirmation" class="form-label">
                            Confirm Password
                        </label>

                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="form-control"
                            placeholder="Confirm your password"
                            required
                        >

                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary w-100 py-2"
                    >
                        Register
                    </button>

                </form>

                <p class="text-center mt-3 mb-0">
                    Already have an account?
                    <a href="{{ route('login') }}">
                        Login here
                    </a>
                </p>

            </div>

        </div>

    </div>

</body>
</html>