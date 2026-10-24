<?php
include 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_id = intval($_POST['event_id']);
    $student_id = $conn->real_escape_string($_POST['student_id']);
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $contact_no = $conn->real_escape_string($_POST['contact_no']);

    $check = $conn->query("SELECT * FROM attendees WHERE event_id = '$event_id' AND student_id = '$student_id'");
    
    if ($check->num_rows > 0) {
        header("Location: index.php?status=duplicate");
    } else {
        $stmt = $conn->prepare("INSERT INTO attendees (event_id, student_id, full_name, email, contact_no) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $event_id, $student_id, $full_name, $email, $contact_no);
        
        if ($stmt->execute()) {
            header("Location: index.php?status=success");
        } else {
            header("Location: index.php?status=error");
        }
    }
}
?>