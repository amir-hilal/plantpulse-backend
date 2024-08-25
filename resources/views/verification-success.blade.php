<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verified</title>
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
        <img src="{{ asset('PlantPulse_logo.png') }}" alt="PlantPulse Logo" class="logo mb-4">
        <h1 class="display-4 text-success">Email Verified!</h1>
        <p class="lead">Thank you for verifying your email. Your account is now active.</p>
        <a href="http://localhost:3000/home" class="btn btn-success">Go to Dashboard</a>
    </div>
</body>
</html>
