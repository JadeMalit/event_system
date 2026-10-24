<?php
session_start();
include '../includes/config.php';
if (!isset($_SESSION['admin'])) { header("Location: ../login.php"); exit(); }

$message = "";
$msg_type = "";

// Handle Add Student
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
    $student_id = $conn->real_escape_string($_POST['student_id']);
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $section = $conn->real_escape_string($_POST['section']);
    $password = $conn->real_escape_string($_POST['password']);

    $chk = $conn->query("SELECT id FROM students WHERE student_id = '$student_id'");
    if ($chk->num_rows > 0) {
        $message = "Student ID already exists!";
        $msg_type = "danger";
    } else {
        $conn->query("INSERT INTO students (student_id, full_name, section, password) VALUES ('$student_id', '$full_name', '$section', '$password')");
        $message = "Student account created successfully!";
        $msg_type = "success";
    }
}

// Handle Edit Student (with optional Profile Photo Update)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_student'])) {
    $id = intval($_POST['id']);
    $student_id = $conn->real_escape_string($_POST['student_id']);
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $section = $conn->real_escape_string($_POST['section']);
    $password = $conn->real_escape_string($_POST['password']);

    // Check if new photo is uploaded by admin
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        $filename = $_FILES['profile_photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_filename = 'profile_' . time() . '_' . $student_id . '.' . $ext;
            $upload_path = '../uploads/' . $new_filename;

            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                $conn->query("UPDATE students SET student_id = '$student_id', full_name = '$full_name', section = '$section', password = '$password', profile_image = '$new_filename' WHERE id = $id");
            }
        }
    } else {
        $conn->query("UPDATE students SET student_id = '$student_id', full_name = '$full_name', section = '$section', password = '$password' WHERE id = $id");
    }

    $message = "Student details updated successfully!";
    $msg_type = "success";
}

// Handle Delete Student
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM students WHERE id = $delete_id");
    header("Location: manage_students.php?status=deleted");
    exit();
}

if (isset($_GET['status']) && $_GET['status'] == 'deleted') {
    $message = "Student account deleted successfully!";
    $msg_type = "warning";
}

$students = $conn->query("SELECT * FROM students ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - EventPass Admin</title>
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

        .badge-section {
            background-color: rgba(6, 182, 212, 0.2) !important;
            color: #38bdf8 !important;
            border: 1px solid rgba(56, 189, 248, 0.4) !important;
            font-weight: 700;
            padding: 0.35rem 0.75rem;
            border-radius: 8px;
            font-size: 0.8rem;
        }

        .student-thumb {
            width: 45px;
            height: 45px;
            border-radius: 50%;
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

        /* Fixed Alert Styling for Dark Theme */
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
        .alert-warning {
            background-color: rgba(245, 158, 11, 0.2) !important;
            border-color: rgba(245, 158, 11, 0.4) !important;
            color: #fbbf24 !important;
        }
    </style>
</head>
<body>

<div class="cyber-grid"></div>

<div class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo-nav">
            <img src="../img/logo.png" alt="Logo">
        </div>
        <span>EventPass Admin</span>
    </div>

    <a href="index.php" class="nav-link-custom">
        <i class="fa-solid fa-chart-pie"></i>
        <span>Dashboard</span>
    </a>

    <a href="manage_students.php" class="nav-link-custom active">
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

<div class="main-content">
    
    <div class="mb-4">
        <h3 class="fw-bold mb-1 text-white">Student Management</h3>
        <p class="text-secondary small mb-0" style="color: #94a3b8 !important;">Pre-register, edit credentials, or delete student accounts.</p>
    </div>

    <?php if($message): ?>
        <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show rounded-3 mb-4 shadow-sm border" role="alert">
            <i class="fa-solid fa-circle-info me-2"></i><?php echo $message; ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Register Student Card -->
    <div class="card card-custom p-4 mb-4">
        <h5 class="fw-bold mb-3 text-white"><i class="fa-solid fa-user-plus me-2" style="color: #818cf8;"></i>Register New Student Account</h5>
        <form method="POST" class="row g-3" autocomplete="off">
            <input type="hidden" name="add_student" value="1">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Student ID</label>
                <input type="text" name="student_id" class="form-control rounded-3" placeholder="Ex. 2022-10042" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Full Name</label>
                <input type="text" name="full_name" class="form-control rounded-3" placeholder="Ex. Juan Dela Cruz" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Section</label>
                <input type="text" name="section" class="form-control rounded-3" placeholder="Ex. 4A / BSIT-3B" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Initial Password</label>
                <input type="text" name="password" class="form-control rounded-3" value="student123" required>
            </div>
            <div class="col-12 text-end mt-4">
                <button type="submit" class="btn btn-primary-custom"><i class="fa-solid fa-save me-1"></i>Save Student Account</button>
            </div>
        </form>
    </div>

    <!-- Student Table Card -->
    <div class="card card-custom p-4">
        <h5 class="fw-bold mb-3 text-white"><i class="fa-solid fa-users me-2" style="color: #818cf8;"></i>Registered Students Directory</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>PHOTO</th>
                        <th>STUDENT ID</th>
                        <th>FULL NAME</th>
                        <th>SECTION</th>
                        <th>PASSWORD</th>
                        <th class="text-end">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($students->num_rows > 0): ?>
                        <?php while($row = $students->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php if(!empty($row['profile_image']) && file_exists('../uploads/'.$row['profile_image'])): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($row['profile_image']); ?>" class="student-thumb" alt="Photo">
                                <?php else: ?>
                                    <div class="student-thumb bg-secondary bg-opacity-25 text-info d-flex align-items-center justify-content-center fw-bold border border-secondary">
                                        <?php echo strtoupper(substr($row['full_name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold text-white"><?php echo htmlspecialchars($row['student_id']); ?></td>
                            <td class="fw-semibold text-white"><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td>
                                <span class="badge-section">
                                    <?php echo htmlspecialchars(!empty($row['section']) ? $row['section'] : 'N/A'); ?>
                                </span>
                            </td>
                            <td><code class="bg-secondary bg-opacity-25 px-2 py-1 rounded text-light border border-secondary"><?php echo htmlspecialchars($row['password']); ?></code></td>
                            <td class="text-end">
                                <button class="btn btn-outline-light btn-sm rounded-3 me-1 px-3" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id']; ?>">
                                    <i class="fa-solid fa-pen-to-square me-1"></i>Edit
                                </button>
                                <a href="manage_students.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-outline-danger btn-sm rounded-3 px-3" onclick="return confirm('Are you sure you want to delete this student account?')">
                                    <i class="fa-solid fa-trash me-1"></i>Delete
                                </a>
                            </td>
                        </tr>

                        <!-- Edit Student Modal -->
                        <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content shadow-lg rounded-4">
                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="fw-bold text-white"><i class="fa-solid fa-user-pen me-2" style="color: #818cf8;"></i>Edit Student Account</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <input type="hidden" name="edit_student" value="1">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            
                                            <div class="mb-3 text-center">
                                                <?php if(!empty($row['profile_image']) && file_exists('../uploads/'.$row['profile_image'])): ?>
                                                    <img src="../uploads/<?php echo htmlspecialchars($row['profile_image']); ?>" class="rounded-circle mb-2 border border-secondary shadow-sm" style="width: 80px; height: 80px; object-fit: cover;" alt="Avatar">
                                                <?php else: ?>
                                                    <div class="rounded-circle mx-auto mb-2 bg-secondary bg-opacity-25 text-info d-flex align-items-center justify-content-center fw-bold fs-3 border border-secondary shadow-sm" style="width: 80px; height: 80px;">
                                                        <?php echo strtoupper(substr($row['full_name'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <label class="form-label small fw-bold text-secondary">Change Profile Photo</label>
                                                    <input type="file" name="profile_photo" class="form-control form-control-sm rounded-3 text-secondary" accept="image/*">
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small fw-bold text-secondary">Student ID</label>
                                                <input type="text" name="student_id" class="form-control rounded-3" value="<?php echo htmlspecialchars($row['student_id']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold text-secondary">Full Name</label>
                                                <input type="text" name="full_name" class="form-control rounded-3" value="<?php echo htmlspecialchars($row['full_name']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold text-secondary">Section</label>
                                                <input type="text" name="section" class="form-control rounded-3" value="<?php echo htmlspecialchars($row['section'] ?? ''); ?>" placeholder="Ex. 4A" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold text-secondary">Reset / Change Password</label>
                                                <input type="text" name="password" class="form-control rounded-3" value="<?php echo htmlspecialchars($row['password']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-0 pt-0 pb-4 px-4">
                                            <button type="button" class="btn btn-outline-light rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary-custom px-4">Update Account</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-secondary py-4">No student records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>