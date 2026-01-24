<?php
include('config.php');
session_start();

// ==========================
// Admin Authentication Check
// ==========================
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// ==========================
// Handle Delete
// ==========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("DELETE FROM event_feedback WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// ==========================
// Fetch all feedbacks with event & user info
// ==========================
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
ORDER BY ef.submitted_at DESC
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Event Feedbacks | Admin Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>

<?php include('admin_navbar.php'); ?>

<div class="container mt-5">
    <h3 class="mb-4">All Event Feedbacks</h3>

    <table class="table table-bordered table-hover">
        <thead class="thead-dark">
            <tr>
                <th>#</th>
                <th>Event</th>
                <th>Participant</th>
                <th>Email</th>
                <th>Rating</th>
                <th>Feedback</th>
                <th>Submitted On</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['feedback_id']; ?></td>
                        <td>
                            <?= htmlspecialchars($row['event_title']); ?>
                            <br>
                            
                        </td>
                        <td><?= htmlspecialchars($row['first_name'].' '.$row['last_name']); ?></td>
                        <td><?= htmlspecialchars($row['email']); ?></td>
                        <td>
                            <?php
                                for ($i=1; $i<=5; $i++) {
                                    echo $i <= $row['rating'] ? '<span style="color:gold;">&#9733;</span>' : '<span style="color:#ccc;">&#9733;</span>';
                                }
                            ?>
                        </td>
                        <td><?= nl2br(htmlspecialchars($row['feedback_text'])); ?></td>
                        <td><?= date("d M Y, h:i A", strtotime($row['submitted_at'])); ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Delete this feedback?');">
                                <input type="hidden" name="id" value="<?= $row['feedback_id']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <button class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">No feedbacks found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
