<?php
session_start();
include '../includes/config.php';
if (!isset($_SESSION['admin'])) { header("Location: ../login.php"); exit(); }

// Mark Attendance
if (isset($_GET['action']) && $_GET['action'] == 'mark_present') {
    $id = intval($_GET['id']);
    $now = date('Y-m-d H:i:s');
    $conn->query("UPDATE attendees SET status = 'Present', checkin_time = '$now' WHERE id = $id");
    header("Location: index.php");
    exit();
}

// Add Event
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_event'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $desc = $conn->real_escape_string($_POST['description']);
    $date = $_POST['event_date'];
    $time = $_POST['event_time'];
    $venue = $conn->real_escape_string($_POST['venue']);
    
    $conn->query("INSERT INTO events (title, description, event_date, event_time, venue) VALUES ('$title', '$desc', '$date', '$time', '$venue')");
    header("Location: index.php");
    exit();
}

// Delete Event
if (isset($_GET['delete_event'])) {
    $eid = intval($_GET['delete_event']);
    $conn->query("DELETE FROM events WHERE id = $eid");
    header("Location: index.php");
    exit();
}

// Stats
$total_events = $conn->query("SELECT COUNT(*) as cnt FROM events")->fetch_assoc()['cnt'];
$total_students = $conn->query("SELECT COUNT(*) as cnt FROM students")->fetch_assoc()['cnt'];
$total_attendees = $conn->query("SELECT COUNT(*) as cnt FROM attendees WHERE status = 'Present'")->fetch_assoc()['cnt'];

// Search and Filter Logic
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$filter_event = isset($_GET['event_filter']) ? intval($_GET['event_filter']) : 0;
$filter_status = isset($_GET['status_filter']) ? $conn->real_escape_string($_GET['status_filter']) : '';

$query = "SELECT attendees.*, events.title as event_title, students.section 
          FROM attendees 
          JOIN events ON attendees.event_id = events.id 
          LEFT JOIN students ON attendees.student_id = students.student_id 
          WHERE 1=1";

if ($search != '') {
    $query .= " AND (attendees.full_name LIKE '%$search%' OR attendees.student_id LIKE '%$search%' OR students.section LIKE '%$search%')";
}
if ($filter_event > 0) {
    $query .= " AND attendees.event_id = $filter_event";
}
if ($filter_status != '') {
    $query .= " AND attendees.status = '$filter_status'";
}
$query .= " ORDER BY attendees.id DESC";
$attendees = $conn->query($query);
$events_list = $conn->query("SELECT * FROM events");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - EventPass</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-bg: #07090e;
            --sidebar-hover: #111827;
            --primary-color: #6366f1;
            --bg-color: #07090e;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            overflow-x: hidden;
        }

        /* Cyber Background Grid */
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

        .sidebar {
            width: 260px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #090d16;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            padding: 1.5rem 1rem;
            z-index: 1000;
        }

        .sidebar-brand {
            font-size: 1.15rem;
            font-weight: 800;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 0.75rem 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 1.5rem;
        }

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

        .nav-link-custom {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #94a3b8;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            margin-bottom: 6px;
            transition: all 0.25s ease;
        }

        .nav-link-custom:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.04);
        }

        .nav-link-custom.active {
            background: linear-gradient(135deg, #6366f1, #a855f7);
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.35);
        }

        .main-content {
            margin-left: 260px;
            padding: 2.5rem;
            position: relative;
            z-index: 10;
        }

        /* Glassmorphism Cards */
        .card-custom {
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5);
            color: #f8fafc;
        }

        .stat-card-clickable, .stat-card-static {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .stat-card-clickable {
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .stat-card-clickable:hover {
            transform: translateY(-3px);
            border-color: rgba(99, 102, 241, 0.4);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #6366f1, #a855f7);
            border: none;
            color: #fff;
            padding: 0.65rem 1.4rem;
            border-radius: 12px;
            font-weight: 700;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.35);
            transition: all 0.25s ease;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(168, 85, 247, 0.5);
            color: #fff;
        }

        /* Fixed Dark Table Styling */
        .table {
            color: #f8fafc !important;
            background-color: transparent !important;
        }

        .table th {
            font-weight: 700;
            color: #94a3b8 !important;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.8px;
            padding: 1.1rem 1rem;
            background-color: rgba(15, 23, 42, 0.95) !important;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1) !important;
        }

        .table td {
            padding: 1.1rem 1rem;
            vertical-align: middle;
            color: #ffffff !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            background-color: rgba(15, 23, 42, 0.6) !important;
        }

        .table-hover tbody tr:hover td {
            background-color: rgba(30, 41, 59, 0.8) !important;
        }

        .badge-present {
            background-color: rgba(220, 252, 231, 0.15);
            color: #4ade80;
            font-weight: 700;
            padding: 0.4rem 0.9rem;
            border-radius: 30px;
            font-size: 0.75rem;
            border: 1px solid rgba(74, 222, 128, 0.3);
        }

        .badge-registered {
            background-color: rgba(254, 243, 199, 0.15);
            color: #facc15;
            font-weight: 700;
            padding: 0.4rem 0.9rem;
            border-radius: 30px;
            font-size: 0.75rem;
            border: 1px solid rgba(250, 204, 21, 0.3);
        }

        .badge-event-title {
            background: rgba(255, 255, 255, 0.08) !important;
            color: #e2e8f0 !important;
            border: 1px solid rgba(255, 255, 255, 0.15);
            font-weight: 600;
            padding: 0.35rem 0.75rem;
            border-radius: 8px;
            font-size: 0.8rem;
        }

        .badge-section {
            background-color: rgba(6, 182, 212, 0.2) !important;
            color: #38bdf8 !important;
            border: 1px solid rgba(56, 189, 248, 0.4) !important;
            font-weight: 700;
            padding: 0.35rem 0.75rem;
            border-radius: 8px;
            font-size: 0.8rem;
        }

        .badge-present-count {
            background-color: #10b981 !important;
            color: #ffffff !important;
            font-weight: 700;
            padding: 0.5rem 1rem;
            border-radius: 50rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 0.85rem;
        }

        .checkin-thumb {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        /* Form Controls Dark Theme */
        .form-control, .form-select {
            background: rgba(15, 23, 42, 0.6) !important;
            border: 1.5px solid rgba(255, 255, 255, 0.12) !important;
            color: #fff !important;
            border-radius: 12px;
            padding: 0.75rem 1rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: #818cf8 !important;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.25) !important;
        }

        .form-control::placeholder {
            color: #64748b;
        }

        /* Modals Dark Theme */
        .modal-content {
            background: #0f172a !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            border-radius: 24px !important;
            color: #f8fafc;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.8) !important;
        }

        .list-group-item {
            background: rgba(15, 23, 42, 0.5) !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            color: #f8fafc !important;
        }
    </style>
</head>
<body>

<div class="cyber-grid"></div>

<!-- Left Navigation Sidebar -->
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo-nav">
            <img src="../img/logo.png" alt="Logo">
        </div>
        <span>EventPass Admin</span>
    </div>

    <a href="index.php" class="nav-link-custom active">
        <i class="fa-solid fa-chart-pie"></i>
        <span>Dashboard</span>
    </a>

    <a href="manage_students.php" class="nav-link-custom">
        <i class="fa-solid fa-user-graduate"></i>
        <span>Manage Students</span>
    </a>

    <div style="position: absolute; bottom: 2rem; width: calc(100% - 2rem);">
        <a href="../logout.php" class="nav-link-custom text-danger" style="background: rgba(239, 68, 68, 0.1);">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<!-- Main Content Area -->
<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-white">Dashboard Overview</h3>
            <p class="text-secondary small mb-0" style="color: #94a3b8 !important;">Monitor event turnouts, verify student selfies, and manage analytics seamlessly.</p>
        </div>
        <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addEventModal">
            <i class="fa-solid fa-plus me-2"></i>Create Event
        </button>
    </div>

    <!-- Quick Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card card-custom stat-card-clickable" data-bs-toggle="modal" data-bs-target="#eventsListModal">
                <div class="stat-icon" style="background: rgba(99, 102, 241, 0.15); color: #818cf8;">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-secondary small fw-bold">TOTAL EVENTS</span>
                        <span class="badge rounded-pill px-2 py-1" style="background: rgba(99, 102, 241, 0.2); color: #818cf8; font-size: 0.65rem;">View <i class="fa-solid fa-arrow-right ms-1"></i></span>
                    </div>
                    <h3 class="fw-bold mb-0 mt-1 text-white"><?php echo $total_events; ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-custom stat-card-static">
                <div class="stat-icon" style="background: rgba(250, 204, 21, 0.15); color: #facc15;">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="flex-grow-1">
                    <span class="text-secondary small fw-bold">REGISTERED STUDENTS</span>
                    <h3 class="fw-bold mb-0 mt-1 text-white"><?php echo $total_students; ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-custom stat-card-static">
                <div class="stat-icon" style="background: rgba(74, 222, 128, 0.15); color: #4ade80;">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="flex-grow-1">
                    <span class="text-secondary small fw-bold">TOTAL PRESENT ATTENDEES</span>
                    <h3 class="fw-bold mb-0 mt-1 text-white"><?php echo $total_attendees; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Scheduled Events Table -->
    <div class="card card-custom p-4 mb-4">
        <h5 class="fw-bold mb-3 text-white"><i class="fa-solid fa-layer-group me-2" style="color: #818cf8;"></i>Scheduled Events</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>EVENT TITLE</th>
                        <th>DATE & TIME</th>
                        <th>VENUE</th>
                        <th class="text-end">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $evs = $conn->query("SELECT * FROM events ORDER BY event_date ASC");
                    if($evs->num_rows > 0):
                        while($ev = $evs->fetch_assoc()): 
                    ?>
                    <tr>
                        <td class="fw-bold text-white"><?php echo htmlspecialchars($ev['title']); ?></td>
                        <td class="text-secondary"><i class="fa-regular fa-clock me-1"></i><?php echo date('M d, Y', strtotime($ev['event_date'])).' @ '. date('h:i A', strtotime($ev['event_time'])); ?></td>
                        <td class="text-secondary"><i class="fa-solid fa-location-dot me-1"></i><?php echo htmlspecialchars($ev['venue']); ?></td>
                        <td class="text-end">
                            <a href="index.php?event_filter=<?php echo $ev['id']; ?>&status_filter=Present#attendeesSection" class="btn btn-outline-light btn-sm rounded-3 me-1 px-3">
                                <i class="fa-solid fa-eye me-1"></i>View Attendees
                            </a>
                            <a href="index.php?delete_event=<?php echo $ev['id']; ?>" class="btn btn-outline-danger btn-sm rounded-3 px-3" onclick="return confirm('Are you sure you want to delete this event?')">
                                <i class="fa-solid fa-trash me-1"></i>Delete
                            </a>
                        </td>
                    </tr>
                    <?php 
                        endwhile; 
                    else:
                    ?>
                    <tr><td colspan="4" class="text-center text-secondary py-3">No events scheduled yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Search & Filter Controls -->
    <div class="card card-custom p-3 mb-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 rounded-start-3 text-secondary border-secondary"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control border-start-0 rounded-end-3 shadow-none text-white border-secondary" placeholder="Search by Student ID, Name, or Section...">
                </div>
            </div>
            <div class="col-md-5">
                <select name="event_filter" class="form-select rounded-3 shadow-none border-secondary text-white">
                    <option value="0" style="background:#0f172a;">All Events</option>
                    <?php 
                    $events_list_select = $conn->query("SELECT * FROM events");
                    while($ef = $events_list_select->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $ef['id']; ?>" style="background:#0f172a;" <?php if($filter_event == $ef['id']) echo 'selected'; ?>><?php echo htmlspecialchars($ef['title']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary-custom w-100 rounded-3"><i class="fa-solid fa-filter me-1"></i>Filter</button>
            </div>
        </form>
    </div>

    <!-- Attendees List Table -->
    <div class="card card-custom p-4" id="attendeesSection">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0 text-white"><i class="fa-solid fa-user-check me-2" style="color: #818cf8;"></i>Attendees & Verification List</h5>
            <?php if($filter_event > 0 || $filter_status != ''): ?>
                <a href="index.php" class="btn btn-outline-light btn-sm rounded-pill px-3"><i class="fa-solid fa-rotate-left me-1"></i>Clear Filter</a>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>SELFIE PROOF</th>
                        <th>STUDENT ID</th>
                        <th>FULL NAME</th>
                        <th>SECTION</th>
                        <th>EVENT</th>
                        <th>STATUS</th>
                        <th class="text-end">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($attendees->num_rows > 0): ?>
                        <?php while($row = $attendees->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php if(!empty($row['checkin_photo']) && file_exists('../uploads/'.$row['checkin_photo'])): ?>
                                        <a href="../uploads/<?php echo htmlspecialchars($row['checkin_photo']); ?>" target="_blank">
                                            <img src="../uploads/<?php echo htmlspecialchars($row['checkin_photo']); ?>" class="checkin-thumb" alt="Selfie">
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-25 text-secondary border border-secondary px-2 py-1">No Selfie</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold text-white"><?php echo htmlspecialchars($row['student_id']); ?></td>
                                <td class="text-white"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><span class="badge-section"><?php echo htmlspecialchars($row['section'] ?? 'N/A'); ?></span></td>
                                <td><span class="badge-event-title"><?php echo htmlspecialchars($row['event_title']); ?></span></td>
                                <td>
                                    <?php if($row['status'] == 'Present'): ?>
                                        <span class="badge-present"><i class="fa-solid fa-circle-check me-1"></i>Present</span>
                                    <?php else: ?>
                                        <span class="badge-registered"><i class="fa-solid fa-clock me-1"></i>Registered</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if($row['status'] != 'Present'): ?>
                                        <a href="index.php?action=mark_present&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-success rounded-3 px-3 fw-bold">
                                            <i class="fa-solid fa-check me-1"></i>Mark Present
                                        </a>
                                    <?php else: ?>
                                        <span class="text-success small fw-bold"><i class="fa-solid fa-check-double me-1"></i>Verified</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center text-secondary py-4">No records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL 1: All Events Directory Modal -->
<div class="modal fade" id="eventsListModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold text-white"><i class="fa-solid fa-calendar-days me-2" style="color: #818cf8;"></i>All Events Directory</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-secondary small mb-3">Click on any event below to download the event's attendance CSV report.</p>
                <div class="list-group rounded-3 border-0">
                    <?php 
                    $all_evs = $conn->query("SELECT events.*, (SELECT COUNT(*) FROM attendees WHERE event_id = events.id AND status = 'Present') as present_cnt FROM events ORDER BY event_date DESC");
                    while($ev_item = $all_evs->fetch_assoc()):
                    ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center p-3 mb-2 rounded-3 shadow-sm">
                        <div>
                            <h6 class="fw-bold mb-1 text-white"><?php echo htmlspecialchars($ev_item['title']); ?></h6>
                            <p class="text-secondary small mb-0"><i class="fa-regular fa-clock me-1"></i><?php echo date('M d, Y', strtotime($ev_item['event_date'])).' @ '.date('h:i A', strtotime($ev_item['event_time'])); ?> | <i class="fa-solid fa-location-dot me-1"></i><?php echo htmlspecialchars($ev_item['venue']); ?></p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge-present-count"><i class="fa-solid fa-user-check me-1"></i><?php echo $ev_item['present_cnt']; ?> Present</span>
                            <a href="export_event_csv.php?event_id=<?php echo $ev_item['id']; ?>" class="btn btn-sm btn-success rounded-pill px-3">
                                <i class="fa-solid fa-file-csv me-1"></i>Export CSV
                            </a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 2: Create New Event Modal -->
<div class="modal fade" id="addEventModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg rounded-4">
            <form method="POST">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold text-white"><i class="fa-solid fa-calendar-plus me-2 text-indigo"></i>Create New Event</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="add_event" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Event Title</label>
                        <input type="text" name="title" class="form-control rounded-3" required placeholder="Ex. IT Tech Symposium 2026" autocomplete="off">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Description</label>
                        <textarea name="description" class="form-control rounded-3" rows="2" required placeholder="Brief summary of the event..."></textarea>
                    </div>
                    
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Event Date</label>
                            <input type="date" name="event_date" class="form-control rounded-3 text-white" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Start Time</label>
                            <input type="time" name="event_time" class="form-control rounded-3 text-white" required>
                        </div>
                    </div>
               
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Venue</label>
                        <input type="text" name="venue" class="form-control rounded-3" required placeholder="Ex. AVR Hall / Gym" autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4">
                    <button type="button" class="btn btn-outline-light rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom px-4">Save Event</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>