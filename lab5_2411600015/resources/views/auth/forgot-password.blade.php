<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>GSCSDA Student Portal - Forgot Password</title>

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
                    FORGOT PASSWORD
                </h4>

                <p class="mb-0">
                    Reset your account password
                </p>

            </div>

            <div class="card-body p-4">

                <p class="text-muted">
                    Enter your registered email address. Laravel will send
                    instructions to reset your password.
                </p>

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

                <form method="POST" action="{{ route('password.email') }}">

                    @csrf

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
                            placeholder="Enter your registered email"
                            required
                            autofocus
                        >

                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary w-100 py-2"
                    >
                        Send Password Reset Link
                    </button>

                </form>

                <p class="text-center mt-3 mb-0">
                    <a href="{{ route('login') }}">
                        Back to Login
                    </a>
                </p>

            </div>

        </div>

    </div>

</body>
</html>