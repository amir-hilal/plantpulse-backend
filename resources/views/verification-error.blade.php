<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification Failed</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 100px;
        }
        .logo {
            width: 150px;
        }
    </style>
</head>
<body>
    <div class="container text-center">
        <img src="{{ asset('plantpulse-logo.png') }}" alt="PlantPulse Logo" class="logo mb-4">
        <h1 class="display-4 text-danger">Email Verification Failed</h1>
        <p class="lead">We could not verify your email. Please try again or contact support if the issue persists.</p>
        <form action="{{ route('verification.resend') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-warning">Resend Verification Email</button>
        </form>
    </div>
</body>
</html>
