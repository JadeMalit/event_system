<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Registration & Attendance System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar-brand { font-weight: bold; color: #4f46e5 !important; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .btn-primary-custom { background-color: #4f46e5; border: none; }
        .btn-primary-custom:hover { background-color: #4338ca; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="fa-solid fa-calendar-check me-2"></i>EventPass</a>
        <div>
            <a href="index.php" class="btn btn-outline-primary btn-sm me-2"><i class="fa-solid fa-house me-1"></i>Home</a>
            <a href="admin/index.php" class="btn btn-primary-custom text-white btn-sm"><i class="fa-solid fa-user-shield me-1"></i>Admin Panel</a>
        </div>
    </div>
</nav>
<div class="container pb-5"></div>