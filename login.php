<?php
session_start();
include 'includes/config.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role = $_POST['role'];

    if ($role == 'admin') {
        $username = $conn->real_escape_string($_POST['username']);
        $password = $_POST['admin_password'];

        $result = $conn->query("SELECT * FROM admins WHERE username = '$username' AND password = '$password'");
        if ($result && $result->num_rows > 0) {
            $_SESSION['admin'] = $username;
            header("Location: admin/index.php");
            exit();
        } else {
            $error = "Invalid Admin Username or Password!";
        }
    } elseif ($role == 'student') {
        $student_id = trim($conn->real_escape_string($_POST['student_id']));
        $password = trim($_POST['student_password']);

        $result = $conn->query("SELECT * FROM students WHERE student_id = '$student_id'");
        if ($result && $result->num_rows > 0) {
            $student = $result->fetch_assoc();
            if ($password === $student['password']) {
                $_SESSION['student_id'] = $student['student_id'];
                $_SESSION['student_name'] = $student['full_name'];
                header("Location: student/dashboard.php");
                exit();
            } else {
                $error = "Incorrect Password!";
            }
        } else {
            $error = "Student ID not found in the system!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EventPass Portal</title>
    <!-- Google Fonts & FontAwesome -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --accent: #a855f7;
            --accent-glow: rgba(168, 85, 247, 0.4);
            --bg-dark: #07090e;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
            perspective: 1000px;
        }

        /* Ambient Cyber Grid Overlay */
        .cyber-grid {
            position: absolute;
            inset: 0;
            background-image: 
                linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: 1;
            mask-image: radial-gradient(circle at center, black 40%, transparent 80%);
            pointer-events: none;
        }

        /* Dynamic Animated Glowing Orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(90px);
            opacity: 0.55;
            z-index: 1;
            pointer-events: none;
            animation: orbFloat 14s ease-in-out infinite alternate;
        }

        .orb-1 {
            width: 420px;
            height: 420px;
            background: radial-gradient(circle, #6366f1 0%, rgba(99, 102, 241, 0) 70%);
            top: -100px;
            left: -100px;
        }

        .orb-2 {
            width: 480px;
            height: 480px;
            background: radial-gradient(circle, #d946ef 0%, rgba(217, 70, 239, 0) 70%);
            bottom: -150px;
            right: -120px;
            animation-delay: -5s;
        }

        .orb-3 {
            width: 320px;
            height: 320px;
            background: radial-gradient(circle, #06b6d4 0%, rgba(6, 182, 212, 0) 70%);
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: -9s;
            opacity: 0.35;
        }

        @keyframes orbFloat {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(60px, 40px) scale(1.15); }
            100% { transform: translate(-40px, -30px) scale(0.95); }
        }

        /* Back Button Style */
        .btn-back-home {
            position: absolute;
            top: 2rem;
            left: 2rem;
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #94a3b8;
            padding: 0.6rem 1.1rem;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            backdrop-filter: blur(12px);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            z-index: 100;
            transition: all 0.25s ease;
        }

        .btn-back-home:hover {
            background: rgba(99, 102, 241, 0.15);
            border-color: rgba(129, 140, 248, 0.4);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.2);
        }

        /* Ultra-Glassmorphism Card & 3D Effect */
        .login-card {
            width: 100%;
            max-width: 450px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 28px;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(30px) saturate(180%);
            -webkit-backdrop-filter: blur(30px) saturate(180%);
            box-shadow: 
                0 30px 70px -15px rgba(0, 0, 0, 0.6),
                0 0 0 1px rgba(255, 255, 255, 0.1) inset,
                0 0 40px rgba(99, 102, 241, 0.15);
            position: relative;
            z-index: 10;
            overflow: hidden;
            padding: 2.75rem 2.25rem;
            transition: transform 0.2s cubic-bezier(0.2, 0, 0, 1), box-shadow 0.3s ease;
            animation: cardEntrance 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes cardEntrance {
            from { opacity: 0; transform: translateY(40px) scale(0.92); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Glowing Border Highlight on Card Top */
        .card-top-glow {
            position: absolute;
            top: 0;
            left: 15%;
            right: 15%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #818cf8, #c084fc, transparent);
            box-shadow: 0 0 15px #818cf8;
        }

        /* Logo Box Design */
        .brand-logo-container {
            width: 76px;
            height: 76px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25), 0 0 20px rgba(99, 102, 241, 0.2);
            margin-bottom: 1.25rem;
            overflow: hidden;
            position: relative;
            transition: transform 0.3s ease;
        }

        .brand-logo-container:hover {
            transform: scale(1.05) rotate(2deg);
        }

        .brand-logo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Segmented Role Switcher Pill Bar */
        .role-switcher {
            background: rgba(255, 255, 255, 0.06);
            padding: 6px;
            border-radius: 16px;
            display: flex;
            position: relative;
            margin-bottom: 1.75rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .role-option {
            flex: 1;
            text-align: center;
            padding: 11px 14px;
            font-size: 0.875rem;
            font-weight: 700;
            color: #94a3b8;
            cursor: pointer;
            border-radius: 12px;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
            z-index: 2;
        }

        .role-option:hover {
            color: #f8fafc;
        }

        .role-option.active {
            color: #ffffff;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.45);
        }

        /* Sliding Form Container Animation */
        .form-slider-wrapper {
            position: relative;
            width: 100%;
            overflow: hidden;
            min-height: 180px;
        }

        .form-panel {
            transition: all 0.45s cubic-bezier(0.4, 0, 0.2, 1);
            width: 100%;
        }

        .form-panel.slide-left-out {
            opacity: 0;
            transform: translateX(-50px) scale(0.95);
            pointer-events: none;
            position: absolute;
            top: 0;
            left: 0;
        }

        .form-panel.slide-right-out {
            opacity: 0;
            transform: translateX(50px) scale(0.95);
            pointer-events: none;
            position: absolute;
            top: 0;
            left: 0;
        }

        .form-panel.active-panel {
            opacity: 1;
            transform: translateX(0) scale(1);
            position: relative;
        }

        /* Inputs & Form Styling */
        .form-label {
            font-weight: 600;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            color: #cbd5e1;
            margin-bottom: 0.4rem;
            text-transform: uppercase;
        }

        .input-group {
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(15, 23, 42, 0.5);
            transition: all 0.3s ease;
        }

        .input-group:focus-within {
            border-color: #818cf8;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.25), 0 0 15px rgba(99, 102, 241, 0.2);
            background: rgba(15, 23, 42, 0.8);
        }

        .input-group-text {
            background: transparent;
            border: none;
            color: #818cf8;
            padding-left: 1.1rem;
            padding-right: 0.5rem;
        }

        .form-control {
            background: transparent;
            border: none;
            padding: 0.85rem 1rem 0.85rem 0.5rem;
            font-weight: 500;
            font-size: 0.95rem;
            color: #f8fafc;
            box-shadow: none !important;
        }

        .form-control::placeholder {
            color: #64748b;
        }

        /* Neon Glowing Button */
        .btn-submit {
            background: linear-gradient(135deg, #6366f1, #a855f7);
            border: none;
            color: #fff;
            font-weight: 800;
            padding: 0.95rem;
            border-radius: 14px;
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.5);
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .btn-submit::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(60deg, transparent, rgba(255, 255, 255, 0.25), transparent);
            transform: rotate(30deg);
            transition: all 0.6s ease;
            opacity: 0;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px -5px rgba(168, 85, 247, 0.65);
            color: #fff;
        }

        .btn-submit:hover::after {
            opacity: 1;
            left: 100%;
        }

        /* Error Alert Styling */
        .alert-custom {
            border-radius: 14px;
            border: 1px solid rgba(239, 68, 68, 0.3);
            background: rgba(239, 68, 68, 0.1);
            color: #fca5a5;
            font-weight: 600;
            font-size: 0.875rem;
            backdrop-filter: blur(10px);
        }

        /* Fullscreen Loading Overlay Styles */
        #loadingOverlay {
            position: fixed;
            inset: 0;
            background: rgba(7, 9, 14, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.35s ease;
        }

        #loadingOverlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .spinner-ring {
            width: 65px;
            height: 65px;
            border: 4px solid rgba(99, 102, 241, 0.2);
            border-top: 4px solid #818cf8;
            border-right: 4px solid #c084fc;
            border-radius: 50%;
            animation: spin 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
            margin-bottom: 1.25rem;
            box-shadow: 0 0 25px rgba(99, 102, 241, 0.4);
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            color: #f8fafc;
            font-weight: 700;
            font-size: 1.05rem;
            letter-spacing: 1px;
        }

        .loading-subtext {
            color: #94a3b8;
            font-size: 0.85rem;
            margin-top: 0.3rem;
        }
    </style>
</head>
<body>

<!-- Back to Home Button -->
<a href="index.php" class="btn-back-home">
    <i class="fa-solid fa-arrow-left"></i> Back to Events
</a>

<!-- Fullscreen Loading Screen Overlay -->
<div id="loadingOverlay">
    <div class="spinner-ring"></div>
    <div class="loading-text">Authenticating...</div>
    <div class="loading-subtext">Verifying credentials, please wait</div>
</div>

<div class="cyber-grid"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>

<div class="login-card" id="tiltCard">
    <div class="card-top-glow"></div>
    
    <div class="text-center mb-4">
        <!-- Logo Integration -->
        <div class="brand-logo-container">
            <img src="img/logo.png" alt="EventPass Logo">
        </div>
        <h4 class="fw-extrabold text-white mb-1" style="letter-spacing: -0.5px;">EventPass Portal</h4>
        <p class="text-secondary small mb-0" style="color: #94a3b8 !important;">Sign in to access your event dashboard</p>
    </div>

    <?php if($error): ?>
        <div class="alert alert-custom p-3 mb-4 d-flex align-items-center gap-2 shadow-sm">
            <i class="fa-solid fa-triangle-exclamation fs-5 text-danger flex-shrink-0"></i>
            <span><?php echo $error; ?></span>
        </div>
    <?php endif; ?>

    <!-- Segmented Control Role Switcher -->
    <div class="role-switcher">
        <div class="role-option active" id="tabStudent" onclick="switchRole('student')">
            <i class="fa-solid fa-user-graduate me-1"></i> Student
        </div>
        <div class="role-option" id="tabAdmin" onclick="switchRole('admin')">
            <i class="fa-solid fa-shield-halved me-1"></i> Administrator
        </div>
    </div>

    <form method="POST" id="mainLoginForm" onsubmit="triggerLoading(event)">
        <input type="hidden" name="role" id="roleInput" value="student">

        <div class="form-slider-wrapper">
            
            <!-- Student Login Panel -->
            <div class="form-panel active-panel" id="studentPanel">
                <div class="mb-3">
                    <label class="form-label">Student ID</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-id-card"></i></span>
                        <input type="text" name="student_id" class="form-control" placeholder="Ex. 2022-10042" autocomplete="off">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" name="student_password" class="form-control" placeholder="••••••••">
                    </div>
                </div>
            </div>

            <!-- Admin Login Panel -->
            <div class="form-panel slide-right-out" id="adminPanel">
                <div class="mb-3">
                    <label class="form-label">Admin Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-user-gear"></i></span>
                        <input type="text" name="username" class="form-control" placeholder="admin" autocomplete="off">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Admin Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-key"></i></span>
                        <input type="password" name="admin_password" class="form-control" placeholder="••••••••">
                    </div>
                </div>
            </div>

        </div>

        <button type="submit" class="btn btn-submit w-100 mt-2">
            Sign In <i class="fa-solid fa-arrow-right ms-2"></i>
        </button>
    </form>
</div>

<script>
function switchRole(role) {
    const roleInput = document.getElementById('roleInput');
    const tabStudent = document.getElementById('tabStudent');
    const tabAdmin = document.getElementById('tabAdmin');
    const studentPanel = document.getElementById('studentPanel');
    const adminPanel = document.getElementById('adminPanel');

    if (role === 'admin' && roleInput.value !== 'admin') {
        roleInput.value = 'admin';
        tabStudent.classList.remove('active');
        tabAdmin.classList.add('active');

        studentPanel.className = 'form-panel slide-left-out';
        adminPanel.className = 'form-panel active-panel';
    } else if (role === 'student' && roleInput.value !== 'student') {
        roleInput.value = 'student';
        tabAdmin.classList.remove('active');
        tabStudent.classList.add('active');

        adminPanel.className = 'form-panel slide-right-out';
        studentPanel.className = 'form-panel active-panel';
    }
}

// Trigger smooth glassmorphism loading screen upon submitting form
function triggerLoading(event) {
    const overlay = document.getElementById('loadingOverlay');
    overlay.classList.add('active');
}

// 3D Card Tilt Interaction
const card = document.getElementById('tiltCard');
document.addEventListener('mousemove', (e) => {
    const { clientX, clientY } = e;
    const { innerWidth, innerHeight } = window;
    
    const rotateX = ((clientY / innerHeight) - 0.5) * -12;
    const rotateY = ((clientX / innerWidth) - 0.5) * 12;
    
    card.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
});

document.addEventListener('mouseleave', () => {
    card.style.transform = `rotateX(0deg) rotateY(0deg)`;
});
</script>
</body>
</html>