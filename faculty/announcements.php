<?php
session_start();
include('config.php');

// ==========================
// Authentication check
// ==========================
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

// ==========================
// Fetch announcements
// ==========================
$sql = "SELECT id, title, message, created_at FROM announcements ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Announcements</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container mt-5">
    <h3 class="mb-4">Announcements</h3>

    <?php if ($result->num_rows > 0): ?>
        <div class="list-group">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="list-group-item mb-3 shadow-sm">
                    <h5 class="mb-1"><?= htmlspecialchars($row['title']); ?></h5>
                    <p class="mb-1"><?= nl2br(htmlspecialchars($row['message'])); ?></p>
                    <small class="text-muted">Posted on: <?= date("d M Y, h:i A", strtotime($row['created_at'])); ?></small>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            No announcements available.
        </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
