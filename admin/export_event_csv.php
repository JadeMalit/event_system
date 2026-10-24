<?php
include '../includes/config.php';

if (isset($_GET['event_id']) && !empty($_GET['event_id'])) {
    $event_id = intval($_GET['event_id']);

    // Kunin ang detalye ng Event para sa filename
    $event_stmt = $conn->prepare("SELECT title FROM events WHERE id = ?");
    $event_stmt->bind_param("i", $event_id);
    $event_stmt->execute();
    $event_result = $event_stmt->get_result();
    $event_data = $event_result->fetch_assoc();
    
    $event_name = $event_data ? preg_replace('/[^a-zA-Z0-9_]/', '_', $event_data['title']) : 'Event';
    $filename = "Attendance_" . $event_name . "_" . date('Y-m-d') . ".csv";

    // Set headers para sa CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '";');

    $output = fopen('php://output', 'w');

    // CSV Header row
    fputcsv($output, array('Student ID', 'Full Name', 'Section', 'Event Title', 'Time In', 'Time Out', 'Status', 'Verified Location (Lat, Lng)'));

    // Query kasama ang section mula sa students table
    $query = "SELECT a.student_id, a.full_name, s.section, e.title as event_title, a.checkin_time, a.timeout_time, a.status, a.user_lat, a.user_lng 
              FROM attendees a 
              JOIN events e ON a.event_id = e.id 
              LEFT JOIN students s ON a.student_id = s.student_id 
              WHERE a.event_id = ? 
              ORDER BY a.checkin_time DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $coords = ($row['user_lat'] && $row['user_lng']) ? $row['user_lat'] . ", " . $row['user_lng'] : 'N/A';
            $time_in = !empty($row['checkin_time']) ? $row['checkin_time'] : 'N/A';
            $time_out = !empty($row['timeout_time']) ? $row['timeout_time'] : 'Not Yet Checked Out';
            $section = !empty($row['section']) ? $row['section'] : 'N/A';

            fputcsv($output, array(
                $row['student_id'],
                $row['full_name'],
                $section,
                $row['event_title'],
                $time_in,
                $time_out,
                $row['status'],
                $coords
            ));
        }
    }

    fclose($output);
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>