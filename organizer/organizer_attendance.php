<?php
session_start();
include('config.php');

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Event Organizer') {
    header("Location: ../login.php");
    exit;
}
$organizer_id = $_SESSION['user_id'];
$alert_message = "";

/* ===============================
   MARK / UNMARK ATTENDANCE
================================ */
if (isset($_GET['toggle_attendance_id'])) {
    $reg_id = intval($_GET['toggle_attendance_id']);

    // Check if this registration belongs to an event of this organizer
    $check = $conn->prepare("
        SELECT er.id, ea.id AS attendance_id
        FROM event_registrations er
        JOIN events e ON er.event_id = e.event_id
        LEFT JOIN event_attendance ea ON er.id = ea.registration_id
        WHERE er.id=? AND e.organizer_id=?
    ");
    $check->bind_param("ii", $reg_id, $organizer_id);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if ($row['attendance_id']) {
            // Already attended -> delete to unmark
            $del = $conn->prepare("DELETE FROM event_attendance WHERE id=?");
            $del->bind_param("i", $row['attendance_id']);
            $del->execute();
            $del->close();
            $alert_message = "Attendance unmarked successfully.";
        } else {
            // Not attended -> insert
            $ins = $conn->prepare("INSERT INTO event_attendance (registration_id, marked_by) VALUES (?, 'organizer')");
            $ins->bind_param("i", $reg_id);
            $ins->execute();
            $ins->close();
            $alert_message = "Attendance marked successfully.";
        }
    } else {
        $alert_message = "Invalid registration.";
    }
}

/* ===============================
   FETCH ATTENDANCE
================================ */
$sql = "
SELECT 
    er.id AS registration_id,
    er.status AS registration_status,
    er.registered_at,
    er.cancellation_reason,
    ea.id AS attendance_id,
    ea.attended_at,

    u.first_name,
    u.last_name,
    u.email,

    e.title AS event_title,
    e.start_datetime,
    e.end_datetime
FROM event_registrations er
JOIN events e ON er.event_id = e.event_id
JOIN users u ON er.user_id = u.id
LEFT JOIN event_attendance ea ON er.id = ea.registration_id
WHERE e.organizer_id = ?
ORDER BY e.start_datetime DESC, u.first_name ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $organizer_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html>
<head>
    <title>Event Attendance | Organizer</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>
    <div id="content">

<div class="container mt-5">
    <h3 class="mb-4">Attendance for My Events</h3>

    <?php if($alert_message): ?>
        <div class="alert alert-info alert-dismissible fade show">
            <?= htmlspecialchars($alert_message); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <table class="table table-bordered table-hover">
        <thead class="thead-dark">
        <tr>
            <th>Event</th>
            <th>Student</th>
            <th>Registration Status</th>
            <th>Attendance Status</th>
            <th>Marked At</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        <?php if($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['event_title']); ?></td>
                    <td><?= htmlspecialchars($row['first_name'].' '.$row['last_name']); ?></td>
                    <td><?= ucfirst($row['registration_status']); ?></td>
                    <td>
                        <?php if($row['attendance_id']): ?>
                            <span class="badge badge-success">Attended</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Not Attended</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $row['attended_at'] ? date("d M Y, h:i A", strtotime($row['attended_at'])) : '-'; ?>
                    </td>
                    <td>
                        <a href="organizer_attendance.php?toggle_attendance_id=<?= $row['registration_id']; ?>" 
                           class="btn btn-sm btn-primary">
                            <?= $row['attendance_id'] ? 'Unmark' : 'Mark'; ?>
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="8" class="text-center">No registrations found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
