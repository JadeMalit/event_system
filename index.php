<?php
include 'includes/config.php'; 

$today_date = date('Y-m-d');

// Kunin lamang ang mga active/upcoming events (awtomatikong mawawala ang mga tapos na)
$result = $conn->query("SELECT * FROM events WHERE event_date >= '$today_date' ORDER BY event_date ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventPass - Upcoming School Events</title>
    <!-- Google Fonts & FontAwesome -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --accent: #a855f7;
            --bg-dark: #07090e;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-dark);
            color: #f8fafc;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* Ambient Cyber Grid Overlay */
        .cyber-grid {
            position: fixed;
            inset: 0;
            background-image: 
                linear-gradient(to right, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: 0;
            pointer-events: none;
        }

        /* Glowing Orbs Background */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.45;
            z-index: 0;
            pointer-events: none;
        }

        .orb-1 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, #6366f1 0%, rgba(99, 102, 241, 0) 70%);
            top: -150px;
            left: -150px;
        }

        .orb-2 {
            width: 550px;
            height: 550px;
            background: radial-gradient(circle, #d946ef 0%, rgba(217, 70, 239, 0) 70%);
            bottom: -200px;
            right: -150px;
        }

        /* Navbar Glassmorphism */
        .navbar-custom {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 1rem 2rem;
        }

        .navbar-brand {
            font-weight: 800;
            color: #fff !important;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        /* Logo Container sa Navbar */
        .brand-logo-nav {
            width: 38px;
            height: 38px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }

        .brand-logo-nav img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Login Button in Navbar */
        .btn-login-nav {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff;
            font-weight: 600;
            padding: 0.5rem 1.25rem;
            border-radius: 12px;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-login-nav:hover {
            background: linear-gradient(135deg, #6366f1, #a855f7);
            border-color: transparent;
            color: #fff;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
        }

        /* Hero Section */
        .hero-section {
            position: relative;
            z-index: 2;
            padding: 4rem 1rem 2rem 1rem;
            text-align: center;
        }

        .hero-title {
            font-weight: 800;
            font-size: 2.75rem;
            letter-spacing: -1px;
            color: #fff;
            margin-bottom: 0.75rem;
        }

        .hero-subtitle {
            color: #94a3b8;
            font-size: 1.05rem;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Events Container & Cards */
        .events-container {
            position: relative;
            z-index: 2;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem 4rem 1rem;
        }

        .card-custom {
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 24px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5);
        }

        .card-custom:hover {
            transform: translateY(-5px);
            border-color: rgba(129, 140, 248, 0.4);
            box-shadow: 0 25px 50px -12px rgba(99, 102, 241, 0.25);
        }

        .badge-custom {
            background: rgba(99, 102, 241, 0.15) !important;
            color: #818cf8 !important;
            border: 1px solid rgba(99, 102, 241, 0.3);
            padding: 0.4rem 0.85rem;
            border-radius: 50rem;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .card-title {
            font-weight: 700;
            font-size: 1.35rem;
            color: #fff;
            margin-top: 0.5rem;
        }

        .card-text {
            color: #94a3b8 !important;
            font-size: 0.925rem;
            line-height: 1.5;
        }

        .text-secondary {
            color: #cbd5e1 !important;
        }

        hr {
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        /* Primary Custom Button */
        .btn-primary-custom {
            background: linear-gradient(135deg, #6366f1, #a855f7);
            border: none;
            font-weight: 700;
            padding: 0.75rem 1rem;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.35);
            transition: all 0.25s ease;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(168, 85, 247, 0.5);
            color: #fff;
        }

        /* Modal Customizations */
        .modal-content {
            background: #0f172a !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            border-radius: 24px !important;
            color: #f8fafc;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.7) !important;
        }

        .modal-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            color: #cbd5e1;
            text-transform: uppercase;
        }

        .form-control {
            background: rgba(15, 23, 42, 0.6) !important;
            border: 1.5px solid rgba(255, 255, 255, 0.12) !important;
            color: #fff !important;
            border-radius: 12px;
            padding: 0.75rem 1rem;
        }

        .form-control:focus {
            border-color: #818cf8 !important;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.25) !important;
        }

        .form-control::placeholder {
            color: #64748b;
        }
    </style>
</head>
<body>

<div class="cyber-grid"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<!-- Navigation Bar -->
<nav class="navbar navbar-custom">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="index.php">
            <div class="brand-logo-nav">
                <img src="img/logo.png" alt="EventPass Logo">
            </div>
            EventPass
        </a>
        <div>
            <a href="login.php" class="btn-login-nav">
                <i class="fa-solid fa-right-to-bracket"></i> Login Portal
            </a>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<div class="hero-section">
    <h2 class="hero-title">Upcoming School Events</h2>
    <p class="hero-subtitle">Register early for a fast and seamless event check-in!</p>
</div>

<!-- Events List Grid -->
<div class="events-container">
    <div class="row g-4">
        <?php if($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card card-custom h-100 p-3">
                        <div class="card-body d-flex flex-column">
                            <span class="badge badge-custom mb-2 align-self-start">
                                <i class="fa-solid fa-calendar me-1"></i><?php echo date('M d, Y', strtotime($row['event_date'])); ?>
                            </span>
                            <h5 class="card-title fw-bold"><?php echo htmlspecialchars($row['title']); ?></h5>
                            <p class="card-text flex-grow-1"><?php echo htmlspecialchars($row['description']); ?></p>
                            <hr>
                            <p class="small text-secondary mb-1"><i class="fa-regular fa-clock me-1"></i><?php echo date('h:i A', strtotime($row['event_time'])); ?></p>
                            <p class="small text-secondary mb-3"><i class="fa-solid fa-location-dot me-1"></i><?php echo htmlspecialchars($row['venue']); ?></p>
                            
                            <button class="btn btn-primary-custom text-white w-100 mt-auto" data-bs-toggle="modal" data-bs-target="#registerModal<?php echo $row['id']; ?>">
                                <i class="fa-solid fa-pen-to-square me-1"></i>Register Now
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal Form -->
                <div class="modal fade" id="registerModal<?php echo $row['id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content shadow">
                            <div class="modal-header border-bottom-0 pb-0">
                                <h5 class="modal-title fs-6 text-white fw-bold"><i class="fa-solid fa-ticket me-2 text-indigo"></i>Register for <?php echo htmlspecialchars($row['title']); ?></h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="register.php" method="POST">
                                <div class="modal-body p-4">
                                    <input type="hidden" name="event_id" value="<?php echo $row['id']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Student ID / Employee No.</label>
                                        <input type="text" name="student_id" class="form-control" required placeholder="e.g., 2022-10042" autocomplete="off">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="full_name" class="form-control" required placeholder="Juan Dela Cruz" autocomplete="off">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" name="email" class="form-control" required placeholder="juan@gmail.com" autocomplete="off">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Contact Number</label>
                                        <input type="text" name="contact_no" class="form-control" placeholder="09123456789" autocomplete="off">
                                    </div>
                                </div>
                                <div class="modal-footer border-0 pt-0 pb-4 px-4">
                                    <button type="button" class="btn btn-outline-light w-45 rounded-3 py-2" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary-custom text-white w-55 rounded-3 py-2">Submit Registration</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center text-muted py-5">
                <i class="fa-regular fa-calendar-xmark fs-1 mb-3 text-secondary"></i>
                <h5>No upcoming events found.</h5>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>