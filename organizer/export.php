<?php
session_start();
include('config.php');

// Authentication Check - Only Event Organizer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Event Organizer') {
    die("Access Denied.");
}

$organizer_id = $_SESSION['user_id'];
$type = $_GET['type'] ?? 'csv';
$data = $_GET['data'] ?? 'events';

// Set headers for CSV download
if ($type === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="uems_' . $data . '_report_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    
    // BOM for Excel to properly read UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
}

// ====================== EXPORT EVENTS ======================
if ($data === 'events') {
    fputcsv($output, ['Event ID', 'Title', 'Category', 'Start Date', 'End Date', 'Capacity', 'Status', 'Registrations', 'Attended', 'Created At']);

    $sql = "SELECT 
                e.event_id,
                e.title,
                COALESCE(ec.name, 'No Category') AS category_name,
                e.start_datetime,
                e.end_datetime,
                e.capacity,
                e.status,
                e.created_at,
                COUNT(er.id) AS total_registrations,
                SUM(CASE WHEN ea.id IS NOT NULL THEN 1 ELSE 0 END) AS attended_count
            FROM events e
            LEFT JOIN event_categories ec ON e.category_id = ec.id
            LEFT JOIN event_registrations er ON e.event_id = er.event_id
            LEFT JOIN event_attendance ea ON er.id = ea.registration_id
            WHERE e.organizer_id = ?
            GROUP BY e.event_id
            ORDER BY e.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $organizer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['event_id'],
            $row['title'],
            $row['category_name'],
            date('d-m-Y H:i', strtotime($row['start_datetime'])),
            date('d-m-Y H:i', strtotime($row['end_datetime'])),
            $row['capacity'] == 0 ? 'Unlimited' : $row['capacity'],
            ucfirst($row['status']),
            $row['total_registrations'],
            $row['attended_count'],
            date('d-m-Y', strtotime($row['created_at']))
        ]);
    }
}

// ================ EXPORT REGISTRATIONS (All Participants) ================
elseif ($data === 'registrations') {
    fputcsv($output, ['Registration ID', 'Event Title', 'Participant Name', 'Email', 'Phone', 'Status', 'Registered At', 'Attended']);

    $sql = "SELECT 
                er.id AS reg_id,
                e.title,
                u.first_name, u.last_name,
                u.email,
                u.phone,
                er.status,
                er.registered_at,
                CASE WHEN ea.id IS NOT NULL THEN 'Yes' ELSE 'No' END AS attended
            FROM event_registrations er
            JOIN events e ON er.event_id = e.event_id
            JOIN users u ON er.user_id = u.id
            LEFT JOIN event_attendance ea ON er.id = ea.registration_id
            WHERE e.organizer_id = ?
            ORDER BY er.registered_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $organizer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['reg_id'],
            $row['title'],
            $row['first_name'] . ' ' . $row['last_name'],
            $row['email'],
            $row['phone'] ?? 'N/A',
            ucfirst(str_replace('_', ' ', $row['status'])),
            date('d-m-Y H:i', strtotime($row['registered_at'])),
            $row['attended']
        ]);
    }
}

// ====================== EXPORT FEEDBACK ======================
elseif ($data === 'feedback') {
    fputcsv($output, ['Event Title', 'Participant Name', 'Email', 'Rating (1-5)', 'Feedback Text', 'Submitted At']);

    $sql = "SELECT 
                e.title,
                u.first_name, u.last_name,
                u.email,
                ef.rating,
                ef.feedback_text,
                ef.submitted_at
            FROM event_feedback ef
            JOIN events e ON ef.event_id = e.event_id
            JOIN users u ON ef.user_id = u.id
            WHERE e.organizer_id = ?
            ORDER BY ef.submitted_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $organizer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['title'],
            $row['first_name'] . ' ' . $row['last_name'],
            $row['email'],
            $row['rating'],
            $row['feedback_text'] ?? 'No comments',
            date('d-m-Y H:i', strtotime($row['submitted_at']))
        ]);
    }
}

else {
    die("Invalid export type.");
}

if ($type === 'csv') {
    fclose($output);
}
exit;