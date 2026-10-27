<?php
session_start();
include '../includes/config.php';
if (!isset($_SESSION['student_id'])) { header("Location: ../login.php"); exit(); }

$student_id = $_SESSION['student_id'];
$message = "";
$msg_type = "";
$today_date = date('Y-m-d');

// Clark College Coordinates (Barangay Anupul, Bamban, Tarlac)
$target_lat = 15.2839;
$target_lng = 120.5731;
$max_allowed_distance_meters = 5000;

// Handle Live Check-in Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['selfie_checkin'])) {
    $attendee_id = intval($_POST['attendee_id']);
    $event_id = intval($_POST['event_id']);
    $user_lat = isset($_POST['user_lat']) ? floatval($_POST['user_lat']) : 0;
    $user_lng = isset($_POST['user_lng']) ? floatval($_POST['user_lng']) : 0;

    $ev_check = $conn->query("SELECT event_date FROM events WHERE id = $event_id")->fetch_assoc();
    
    if ($ev_check) {
        $event_date = $ev_check['event_date'];

        if ($today_date < $event_date) {
            $message = "Event has not started yet (" . date('M d, Y', strtotime($event_date)) . ").";
            $msg_type = "danger";
        } else {
            if (!empty($_POST['captured_image_data'])) {
                $img_data = $_POST['captured_image_data'];
                $img_data = str_replace('data:image/jpeg;base64,', '', $img_data);
                $img_data = str_replace(' ', '+', $img_data);
                $data = base64_decode($img_data);

                if (!file_exists('../uploads')) {
                    mkdir('../uploads', 0777, true);
                }

                $new_filename = 'checkin_' . time() . '_' . $student_id . '.jpg';
                $upload_path = '../uploads/' . $new_filename;

                if (file_put_contents($upload_path, $data)) {
                    $now = date('Y-m-d H:i:s');
                    $conn->query("UPDATE attendees SET checkin_photo = '$new_filename', checkin_time = '$now', user_lat = '$user_lat', user_lng = '$user_lng' WHERE id = $attendee_id AND student_id = '$student_id'");
                    $message = "Live selfie & Geo-location verified! Attendance submitted successfully.";
                    $msg_type = "success";
                } else {
                    $message = "Failed to process live captured image.";
                    $msg_type = "danger";
                }
            } else {
                $message = "Please capture a live camera photo before submitting.";
                $msg_type = "danger";
            }
        }
    }
}

// Handle Profile Image Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_photo'])) {
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        $filename = $_FILES['profile_photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            if (!file_exists('../uploads')) { mkdir('../uploads', 0777, true); }
            $new_filename = 'profile_' . time() . '_' . $student_id . '.' . $ext;
            $upload_path = '../uploads/' . $new_filename;

            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                $conn->query("UPDATE students SET profile_image = '$new_filename' WHERE student_id = '$student_id'");
                $message = "Profile photo updated successfully!";
                $msg_type = "success";
            }
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    $temp_q = $conn->query("SELECT password FROM students WHERE student_id = '$student_id'")->fetch_assoc();

    if ($current_pass !== $temp_q['password']) {
        $message = "Current password does not match!";
        $msg_type = "danger";
    } elseif ($new_pass !== $confirm_pass) {
        $message = "New passwords do not match!";
        $msg_type = "danger";
    } else {
        $conn->query("UPDATE students SET password = '$new_pass' WHERE student_id = '$student_id'");
        $message = "Password updated successfully!";
        $msg_type = "success";
    }
}

// Handle Event Registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_event'])) {
    $event_id = intval($_POST['event_id']);
    $st_q = $conn->query("SELECT * FROM students WHERE student_id = '$student_id'")->fetch_assoc();
    $chk = $conn->query("SELECT * FROM attendees WHERE event_id = $event_id AND student_id = '$student_id'");
    if ($chk->num_rows == 0) {
        $email = $st_q['email'] ?? '';
        $student_name = $st_q['full_name'];
        $conn->query("INSERT INTO attendees (event_id, student_id, full_name, email) VALUES ($event_id, '$student_id', '$student_name', '$email')");
        $message = "Successfully registered for the event!";
        $msg_type = "success";
    }
}

// Fetch Student Data
$student_q = $conn->query("SELECT * FROM students WHERE student_id = '$student_id'");
$student_data = $student_q->fetch_assoc();
$student_name = $student_data['full_name'];

$events = $conn->query("SELECT * FROM events WHERE event_date >= '$today_date' ORDER BY event_date ASC");$my_events = $conn->query("SELECT attendees.*, events.title, events.event_date, events.event_time, events.venue, events.id as real_event_id FROM attendees JOIN events ON attendees.event_id = events.id WHERE attendees.student_id = '$student_id'");

// Verified Present Count for Badges & Streak
$present_check = $conn->query("SELECT attendees.*, events.title FROM attendees JOIN events ON attendees.event_id = events.id WHERE attendees.student_id = '$student_id' AND attendees.status = 'Present'");
$present_count = $present_check->num_rows;

// Gamification Badge Logic
$badge_title = "Newbie Attendee";
$badge_icon = "fa-seedling";
$badge_color = "#64748b";

if ($present_count >= 5) {
    $badge_title = "Campus Vanguard";
    $badge_icon = "fa-crown";
    $badge_color = "#eab308";
} elseif ($present_count >= 3) {
    $badge_title = "Event Enthusiast";
    $badge_icon = "fa-fire";
    $badge_color = "#f97316";
} elseif ($present_count >= 1) {
    $badge_title = "First Pass Achiever";
    $badge_icon = "fa-medal";
    $badge_color = "#3b82f6";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Student Portal - EventPass</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-gradient: linear-gradient(135deg, #6366f1, #a855f7);
            --bg-color: #0f172a;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            overflow-x: hidden;
        }

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

        .navbar-custom {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            position: relative;
            z-index: 100;
        }

        .brand-logo-nav {
            width: 36px;
            height: 36px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
            flex-shrink: 0;
        }

        .brand-logo-nav img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .student-avatar-nav {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-color);
            flex-shrink: 0;
        }

        .card-custom {
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5);
            color: #f8fafc;
        }

        .btn-primary-custom {
            background: var(--primary-gradient);
            border: none;
            color: #fff !important;
            padding: 0.6rem 1.2rem;
            border-radius: 12px;
            font-weight: 700;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.35);
            transition: all 0.25s ease;
            width: 100%;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(168, 85, 247, 0.5);
            color: #fff !important;
        }

        /* ONE-LINE HORIZONTAL SCROLLABLE TABS FIX */
        .tabs-scroll-wrapper {
            width: 100%;
            overflow-x: auto;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        .tabs-scroll-wrapper::-webkit-scrollbar {
            display: none;
        }

        .nav-pills-horizontal {
            display: inline-flex !important;
            flex-wrap: nowrap !important;
            gap: 6px;
            margin: 0;
            padding: 2px;
            width: max-content;
            min-width: 100%;
        }

        .nav-pills-horizontal .nav-item {
            flex: 0 0 auto;
        }

        .nav-pills-horizontal .nav-link {
            color: #94a3b8;
            font-weight: 700;
            padding: 0.55rem 1rem;
            border-radius: 12px;
            font-size: 0.82rem;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .nav-pills-horizontal .nav-link.active {
            background: var(--primary-gradient);
            color: #ffffff !important;
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.35);
        }

        .table {
            color: #f8fafc !important;
            background-color: transparent !important;
        }

        .table th {
            font-weight: 700;
            color: #94a3b8 !important;
            text-transform: uppercase;
            font-size: 0.65rem;
            letter-spacing: 0.8px;
            padding: 0.9rem 0.75rem;
            background-color: rgba(15, 23, 42, 0.95) !important;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1) !important;
        }

        .table td {
            padding: 0.9rem 0.75rem;
            vertical-align: middle;
            color: #ffffff !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            background-color: rgba(15, 23, 42, 0.6) !important;
            font-size: 0.85rem;
        }

        .badge-status-registered {
            background-color: rgba(245, 158, 11, 0.2) !important;
            color: #fbbf24 !important;
            border: 1px solid rgba(245, 158, 11, 0.5) !important;
            font-weight: 700;
            padding: 0.35rem 0.7rem;
            border-radius: 50rem;
            font-size: 0.68rem;
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
        }

        .badge-status-present {
            background-color: rgba(16, 185, 129, 0.2) !important;
            color: #34d399 !important;
            border: 1px solid rgba(16, 185, 129, 0.5) !important;
            font-weight: 700;
            padding: 0.35rem 0.7rem;
            border-radius: 50rem;
            font-size: 0.68rem;
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
        }

        .badge-status-submitted {
            background-color: rgba(56, 189, 248, 0.2) !important;
            color: #38bdf8 !important;
            border: 1px solid rgba(56, 189, 248, 0.5) !important;
            font-weight: 700;
            padding: 0.35rem 0.7rem;
            border-radius: 50rem;
            font-size: 0.68rem;
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
        }

        .btn-registered-disabled {
            background-color: rgba(16, 185, 129, 0.18) !important;
            color: #34d399 !important;
            border: 1px solid rgba(16, 185, 129, 0.4) !important;
            font-weight: 700;
            padding: 0.6rem 1rem;
            border-radius: 12px;
            width: 100%;
            cursor: not-allowed;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        .camera-box {
            width: 100%;
            max-height: 240px;
            background: #07090e;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 16px;
            overflow: hidden;
            position: relative;
        }

        #cameraFeed, #capturedCanvas {
            width: 100%;
            height: auto;
            border-radius: 16px;
            object-fit: cover;
        }

        .badge-card {
            background: rgba(15, 23, 42, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            border-radius: 18px;
            padding: 1rem;
            backdrop-filter: blur(10px);
        }

        .profile-img-preview {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.2);
        }

        .form-control {
            background: rgba(15, 23, 42, 0.6) !important;
            border: 1.5px solid rgba(255, 255, 255, 0.12) !important;
            color: #fff !important;
            border-radius: 12px;
        }

        .form-control:focus {
            border-color: #818cf8 !important;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.25) !important;
        }

        .modal-content {
            background: #0f172a !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            border-radius: 20px !important;
            color: #f8fafc;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.8) !important;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.2) !important;
            border-color: rgba(16, 185, 129, 0.4) !important;
            color: #34d399 !important;
        }
        .alert-danger {
            background-color: rgba(239, 68, 68, 0.2) !important;
            border-color: rgba(239, 68, 68, 0.4) !important;
            color: #f87171 !important;
        }

        @media (max-width: 576px) {
            .container { padding-left: 10px; padding-right: 10px; }
            .navbar-brand span { font-size: 0.95rem !important; }
        }
    </style>
</head>
<body>

<div class="cyber-grid"></div>

<!-- Top Navigation Bar -->
<nav class="navbar navbar-custom py-2 mb-3 sticky-top">
    <div class="container d-flex justify-content-between align-items-center">
        <!-- Left Side: Logo & Brand Name -->
        <a class="navbar-brand fw-bold text-white d-flex align-items-center gap-2 m-0" href="#">
            <div class="brand-logo-nav">
                <img src="../img/logo.png" alt="Logo">
            </div>
            <span class="fs-6 fw-bold" style="letter-spacing: -0.5px;">EventPass</span>
        </a>

        <!-- Upper Right Side: Student Profile Avatar & Logout Button -->
        <div class="d-flex align-items-center gap-2 ms-auto">
            <div class="d-flex align-items-center gap-2 bg-secondary bg-opacity-25 p-1 pe-2 rounded-pill border border-secondary border-opacity-25">
                <?php if(!empty($student_data['profile_image']) && file_exists('../uploads/'.$student_data['profile_image'])): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($student_data['profile_image']); ?>" class="student-avatar-nav" alt="Profile">
                <?php else: ?>
                    <div class="student-avatar-nav bg-indigo text-white d-flex align-items-center justify-content-center fw-bold small" style="background: #6366f1;">
                        <?php echo strtoupper(substr($student_name, 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <div class="d-none d-md-block">
                    <div class="fw-bold text-white small leading-tight"><?php echo htmlspecialchars($student_name); ?></div>
                    <div class="text-secondary" style="font-size: 0.65rem;">ID: <?php echo $student_id; ?></div>
                </div>
            </div>
            <a href="../logout.php" class="btn btn-outline-danger btn-sm rounded-3 px-2 py-1 fw-bold" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
    </div>
</nav>

<div class="container pb-4" style="position: relative; z-index: 10;">

    <!-- Gamified Badge Banner -->
    <div class="badge-card mb-3 d-flex align-items-center justify-content-between shadow-sm">
        <div class="d-flex align-items-center gap-3">
            <div class="p-2 rounded-circle text-white d-flex align-items-center justify-content-center flex-shrink-0" style="background: <?php echo $badge_color; ?>; width: 42px; height: 42px; font-size: 1.1rem;">
                <i class="fa-solid <?php echo $badge_icon; ?>"></i>
            </div>
            <div>
                <div class="text-secondary fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.6rem;">Attendance Level</div>
                <h6 class="fw-bold mb-0 text-white" style="font-size: 0.95rem;"><?php echo $badge_title; ?></h6>
            </div>
        </div>
        <div class="text-end flex-shrink-0">
            <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-30 rounded-pill px-2 py-1 fw-bold" style="font-size: 0.75rem;"><i class="fa-solid fa-fire me-1"></i><?php echo $present_count; ?> Streak</span>
        </div>
    </div>

    <?php if($message): ?>
        <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show rounded-4 mb-3 shadow-sm border small" role="alert">
            <i class="fa-solid fa-circle-info me-2"></i><?php echo $message; ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Navigation Tabs (Straight Horizontal Line Scrollable) -->
    <div class="card-custom p-2 mb-3 shadow-sm">
        <div class="tabs-scroll-wrapper">
            <ul class="nav nav-pills nav-pills-horizontal" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="pills-events-tab" data-bs-toggle="pill" data-bs-target="#pills-events" type="button"><i class="fa-solid fa-calendar-days me-2"></i>Events</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="pills-history-tab" data-bs-toggle="pill" data-bs-target="#pills-history" type="button"><i class="fa-solid fa-clock-rotate-left me-2"></i>Registrations</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="pills-settings-tab" data-bs-toggle="pill" data-bs-target="#pills-settings" type="button"><i class="fa-solid fa-user-gear me-2"></i>Settings</button>
                </li>
            </ul>
        </div>
    </div>

    <div class="tab-content" id="pills-tabContent">
        
        <!-- Tab 1: Available Events -->
        <div class="tab-pane fade show active" id="pills-events">
            <div class="row g-3">
                <?php 
                $events->data_seek(0);
                while($ev = $events->fetch_assoc()): 
                    $ev_id = $ev['id'];
                    $registered = $conn->query("SELECT * FROM attendees WHERE event_id = $ev_id AND student_id = '$student_id'");
                    $is_registered = $registered->num_rows > 0;
                ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card card-custom h-100 p-3">
                        <div class="card-body d-flex flex-column p-1">
                            <span class="badge bg-indigo bg-opacity-25 text-info mb-2 align-self-start px-3 py-2 rounded-pill fw-bold border border-info border-opacity-25" style="font-size: 0.7rem;">
                                <i class="fa-solid fa-calendar me-1"></i><?php echo date('M d, Y', strtotime($ev['event_date'])); ?>
                            </span>
                            <h5 class="fw-bold text-white mt-1 fs-5"><?php echo htmlspecialchars($ev['title']); ?></h5>
                            <p class="text-secondary small flex-grow-1 mb-3" style="font-size: 0.85rem;"><?php echo htmlspecialchars($ev['description']); ?></p>
                            <hr class="border-secondary my-2">
                            <div class="d-flex justify-content-between text-secondary small fw-semibold mb-3" style="font-size: 0.8rem;">
                                <span><i class="fa-regular fa-clock me-1 text-indigo"></i><?php echo date('h:i A', strtotime($ev['event_time'])); ?></span>
                                <span><i class="fa-solid fa-location-dot me-1 text-indigo"></i><?php echo htmlspecialchars($ev['venue']); ?></span>
                            </div>
                            
                            <?php if(!$is_registered): ?>
                                <form method="POST">
                                    <input type="hidden" name="register_event" value="1">
                                    <input type="hidden" name="event_id" value="<?php echo $ev['id']; ?>">
                                    <button type="submit" class="btn btn-primary-custom py-2">Register Now</button>
                                </form>
                            <?php else: ?>
                                <div class="btn-registered-disabled">
                                    <i class="fa-solid fa-check me-2"></i>Registered
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Tab 2: Registrations History -->
        <div class="tab-pane fade" id="pills-history">
            <div class="card card-custom p-3">
                <h5 class="fw-bold mb-3 fs-6 text-white"><i class="fa-solid fa-list-check me-2" style="color: #818cf8;"></i>My Event Pass & Attendance History</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>EVENT</th>
                                <th>STATUS</th>
                                <th class="text-end">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($my_events->num_rows > 0): ?>
                                <?php 
                                $modal_list = array();
                                while($me = $my_events->fetch_assoc()): 
                                    $modal_list[] = $me;
                                    $is_present = ($me['status'] == 'Present' || !empty($me['checkin_photo']));
                                    
                                    $event_date = $me['event_date'];
                                    $is_event_ready = ($today_date >= $event_date);
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-white"><?php echo htmlspecialchars($me['title']); ?></div>
                                        <div class="text-secondary" style="font-size: 0.65rem;"><i class="fa-regular fa-clock me-1"></i><?php echo date('M d, Y', strtotime($me['event_date'])); ?></div>
                                    </td>
                                    <td>
                                        <?php if($me['status'] == 'Present'): ?>
                                            <span class="badge-status-present"><i class="fa-solid fa-circle-check me-1"></i>Present</span>
                                        <?php elseif(!empty($me['checkin_photo'])): ?>
                                            <span class="badge-status-submitted"><i class="fa-solid fa-clock-rotate-left me-1"></i>Submitted</span>
                                        <?php else: ?>
                                            <span class="badge-status-registered"><i class="fa-solid fa-clock me-1"></i>Registered</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if($is_present): ?>
                                            <span class="text-success small fw-bold" style="font-size: 0.7rem;"><i class="fa-solid fa-check-double me-1"></i>Verified</span>
                                        <?php elseif(!$is_event_ready): ?>
                                            <button class="btn btn-secondary btn-sm rounded-3 py-1 px-2" style="font-size: 0.7rem; white-space: nowrap;" disabled>
                                                <i class="fa-solid fa-lock me-1"></i>Locked (<?php echo date('M d', strtotime($event_date)); ?>)
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-primary-custom btn-sm rounded-3 py-1 px-2 fw-bold" style="font-size: 0.7rem; width: auto;" onclick="openGeoCameraModal(<?php echo $me['id']; ?>)">
                                                <i class="fa-solid fa-camera me-1"></i>Check-in
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center text-secondary py-3">No registrations found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 3: Account Settings -->
        <div class="tab-pane fade" id="pills-settings">
            <div class="row g-3">
                <div class="col-12 col-md-5">
                    <div class="card card-custom p-3 text-center">
                        <h6 class="fw-bold mb-3 text-start text-white fs-6"><i class="fa-solid fa-image me-2" style="color: #818cf8;"></i>Profile Photo</h6>
                        <div class="mb-3">
                            <?php if(!empty($student_data['profile_image']) && file_exists('../uploads/'.$student_data['profile_image'])): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($student_data['profile_image']); ?>" class="profile-img-preview mx-auto" alt="Profile Photo">
                            <?php else: ?>
                                <div class="profile-img-preview mx-auto bg-secondary bg-opacity-25 text-info d-flex align-items-center justify-content-center fw-bold fs-2 border border-secondary">
                                    <?php echo strtoupper(substr($student_name, 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="upload_photo" value="1">
                            <div class="mb-3">
                                <input type="file" name="profile_photo" class="form-control form-control-sm rounded-3 text-secondary" accept="image/*" required>
                            </div>
                            <button type="submit" class="btn btn-primary-custom btn-sm py-2"><i class="fa-solid fa-upload me-1"></i>Upload Photo</button>
                        </form>
                    </div>
                </div>

                <div class="col-12 col-md-7">
                    <div class="card card-custom p-3">
                        <h6 class="fw-bold mb-3 text-white fs-6"><i class="fa-solid fa-key me-2" style="color: #818cf8;"></i>Change Password</h6>
                        <form method="POST">
                            <input type="hidden" name="change_password" value="1">
                            <div class="mb-2">
                                <label class="form-label small fw-bold text-secondary mb-1">Current Password</label>
                                <input type="password" name="current_password" class="form-control form-control-sm rounded-3" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-bold text-secondary mb-1">New Password</label>
                                <input type="password" name="new_password" class="form-control form-control-sm rounded-3" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-secondary mb-1">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control form-control-sm rounded-3" required>
                            </div>
                            <button type="submit" class="btn btn-primary-custom btn-sm py-2">Update Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Live Camera & Geo-fencing Modal -->
<?php if(!empty($modal_list)): ?>
    <?php foreach($modal_list as $me): ?>
        <div class="modal fade" id="selfieModal<?php echo $me['id']; ?>" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-lg rounded-4 p-2">
                    <form method="POST" id="attendanceForm<?php echo $me['id']; ?>">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="fw-bold fs-6 text-white"><i class="fa-solid fa-camera me-2" style="color: #818cf8;"></i>Clark College Geo-Verification</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="stopCameraStream(<?php echo $me['id']; ?>)"></button>
                        </div>
                        <div class="modal-body p-2 text-center">
                            
                            <div id="geoStatusBox<?php echo $me['id']; ?>" class="alert alert-secondary small rounded-3 p-2 mb-2 bg-opacity-25 border border-secondary text-light">
                                <i class="fa-solid fa-spinner fa-spin me-1"></i> Verifying location via GPS...
                            </div>

                            <div class="camera-box mb-2 d-flex align-items-center justify-content-center">
                                <video id="cameraFeed<?php echo $me['id']; ?>" autoplay playsinline></video>
                                <canvas id="capturedCanvas<?php echo $me['id']; ?>" class="d-none"></canvas>
                            </div>

                            <input type="hidden" name="selfie_checkin" value="1">
                            <input type="hidden" name="attendee_id" value="<?php echo $me['id']; ?>">
                            <input type="hidden" name="event_id" value="<?php echo $me['real_event_id']; ?>">
                            <input type="hidden" name="user_lat" id="userLatInput<?php echo $me['id']; ?>">
                            <input type="hidden" name="user_lng" id="userLngInput<?php echo $me['id']; ?>">
                            <input type="hidden" name="captured_image_data" id="capturedImageData<?php echo $me['id']; ?>">

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-light btn-sm rounded-3 w-50 py-1" id="snapBtn<?php echo $me['id']; ?>" onclick="takeLiveSnap(<?php echo $me['id']; ?>)" disabled>
                                    <i class="fa-solid fa-camera me-1"></i>Snap Photo
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm rounded-3 w-50 py-1 d-none" id="retakeBtn<?php echo $me['id']; ?>" onclick="retakeSnap(<?php echo $me['id']; ?>)">
                                    <i class="fa-solid fa-rotate-right me-1"></i>Retake
                                </button>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-outline-light btn-sm rounded-3 px-3 py-1" data-bs-dismiss="modal" onclick="stopCameraStream(<?php echo $me['id']; ?>)">Cancel</button>
                            <button type="submit" class="btn btn-primary-custom btn-sm px-3 py-1" id="submitBtn<?php echo $me['id']; ?>" style="width: auto;" disabled>
                                <i class="fa-solid fa-paper-plane me-1"></i>Submit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const targetLat = <?php echo $target_lat; ?>;
    const targetLng = <?php echo $target_lng; ?>;
    const maxRadiusMeters = <?php echo $max_allowed_distance_meters; ?>;
    let cameraStreams = {};

    function calculateDistanceMeters(lat1, lon1, lat2, lon2) {
        const R = 6371e3; 
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c; 
    }

    function openGeoCameraModal(id) {
        const modalElement = document.getElementById('selfieModal' + id);
        const modal = new bootstrap.Modal(modalElement);
        modal.show();

        const statusBox = document.getElementById('geoStatusBox' + id);
        const snapBtn = document.getElementById('snapBtn' + id);

        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const uLat = position.coords.latitude;
                    const uLng = position.coords.longitude;
                    
                    document.getElementById('userLatInput' + id).value = uLat;
                    document.getElementById('userLngInput' + id).value = uLng;

                    const distance = calculateDistanceMeters(uLat, uLng, targetLat, targetLng);

                    if (distance <= maxRadiusMeters) {
                        statusBox.className = "alert alert-success small rounded-3 p-2 mb-2";
                        statusBox.innerHTML = "<i class='fa-solid fa-location-dot me-1'></i> Verified: Inside campus bounds (" + Math.round(distance) + "m away).";
                        snapBtn.disabled = false;
                        startCameraStream(id);
                    } else {
                        statusBox.className = "alert alert-danger small rounded-3 p-2 mb-2";
                        statusBox.innerHTML = "<i class='fa-solid fa-triangle-exclamation me-1'></i> Access Denied: Outside campus bounds (" + Math.round(distance) + "m away).";
                        snapBtn.disabled = true;
                    }
                },
                (error) => {
                    statusBox.className = "alert alert-warning small rounded-3 p-2 mb-2 text-warning";
                    statusBox.innerHTML = "<i class='fa-solid fa-triangle-exclamation me-1'></i> GPS Error: Please allow location access in your browser.";
                    snapBtn.disabled = true;
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        } else {
            statusBox.className = "alert alert-danger small rounded-3 p-2 mb-2";
            statusBox.innerHTML = "Geolocation is not supported by your browser.";
        }
    }

    function startCameraStream(id) {
        const video = document.getElementById('cameraFeed' + id);
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false })
            .then((stream) => {
                cameraStreams[id] = stream;
                video.srcObject = stream;
            })
            .catch((err) => {
                alert("Unable to access front camera. Please check permissions.");
            });
    }

    function stopCameraStream(id) {
        if (cameraStreams[id]) {
            cameraStreams[id].getTracks().forEach(track => track.stop());
            delete cameraStreams[id];
        }
    }

    function takeLiveSnap(id) {
        const video = document.getElementById('cameraFeed' + id);
        const canvas = document.getElementById('capturedCanvas' + id);
        const snapBtn = document.getElementById('snapBtn' + id);
        const retakeBtn = document.getElementById('retakeBtn' + id);
        const submitBtn = document.getElementById('submitBtn' + id);

        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        const imageDataURL = canvas.toDataURL('image/jpeg');
        document.getElementById('capturedImageData' + id).value = imageDataURL;

        video.classList.add('d-none');
        canvas.classList.remove('d-none');

        snapBtn.classList.add('d-none');
        retakeBtn.classList.remove('d-none');
        submitBtn.disabled = false;
        stopCameraStream(id);
    }

    function retakeSnap(id) {
        const video = document.getElementById('cameraFeed' + id);
        const canvas = document.getElementById('capturedCanvas' + id);
        const snapBtn = document.getElementById('snapBtn' + id);
        const retakeBtn = document.getElementById('retakeBtn' + id);
        const submitBtn = document.getElementById('submitBtn' + id);

        video.classList.remove('d-none');
        canvas.classList.add('d-none');

        retakeBtn.classList.add('d-none');
        snapBtn.classList.remove('d-none');
        submitBtn.disabled = true;

        startCameraStream(id);
    }
</script>
</body>
</html>