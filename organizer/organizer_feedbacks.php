<?php
session_start();
include('config.php');

// ==========================
// Authentication Check
// ==========================
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Event Organizer') {
    header("Location: ../login.php");
    exit;
}

$organizer_id = $_SESSION['user_id'];

/* ===============================
   Fetch Feedbacks
================================ */
$sql = "
SELECT 
    ef.id AS feedback_id,
    ef.rating,
    ef.feedback_text,
    ef.submitted_at,
    u.first_name,
    u.last_name,
    u.email,
    e.title AS event_title,
    e.event_id
FROM event_feedback ef
INNER JOIN events e ON ef.event_id = e.event_id
INNER JOIN users u ON ef.user_id = u.id
WHERE e.organizer_id = ?
ORDER BY ef.submitted_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $organizer_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Event Feedbacks | Organizer</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>
    <div id="content">

<div class="container mt-5">
    <h3 class="mb-4">Feedbacks for My Events</h3>

    <table class="table table-bordered table-hover">
        <thead class="thead-dark">
        <tr>
            <th>Event</th>
            <th>Participant</th>
            <th>Email</th>
            <th>Rating</th>
            <th>Feedback</th>
            <th>Submitted On</th>
        </tr>
        </thead>
        <tbody>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($row['event_title']); ?>
                        <br>
                         
                    </td>
                    <td><?= htmlspecialchars($row['first_name'].' '.$row['last_name']); ?></td>
                    <td><?= htmlspecialchars($row['email']); ?></td>
                    <td>
                        <?php
                        // Display stars for rating
                        for ($i=1; $i<=5; $i++) {
                            if ($i <= $row['rating']) {
                                echo '<span style="color:gold;">&#9733;</span>';
                            } else {
                                echo '<span style="color:#ccc;">&#9733;</span>';
                            }
                        }
                        ?>
                    </td>
                    <td><?= nl2br(htmlspecialchars($row['feedback_text'])); ?></td>
                    <td><?= date("d M Y, h:i A", strtotime($row['submitted_at'])); ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" class="text-center">No feedbacks submitted yet.</td>
            </tr>
        <?php endif; ?>

        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
