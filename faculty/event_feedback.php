<?php
session_start();
include('config.php');

// ==========================
// Authentication check
// ==========================
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Faculty') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$event_id = intval($_GET['event_id'] ?? 0);

if (!$event_id) {
    die("Invalid Event ID.");
}

// ==========================
// Check if Faculty is registered for the event
// ==========================
$reg_stmt = $conn->prepare("
    SELECT id, status
    FROM event_registrations
    WHERE event_id = ? AND user_id = ?
");
$reg_stmt->bind_param("ii", $event_id, $user_id);
$reg_stmt->execute();
$reg_res = $reg_stmt->get_result();
$registration = $reg_res->fetch_assoc();
$reg_stmt->close();

if (!$registration || $registration['status'] !== 'confirmed') {
    die("You must be registered and confirmed to submit feedback.");
}

// ==========================
// Handle feedback submission
// ==========================
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating'] ?? 0);
    $feedback_text = trim($_POST['feedback_text'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $message = "Please provide a valid rating between 1 and 5.";
    } else {
        // Insert or update feedback
        $stmt = $conn->prepare("
            INSERT INTO event_feedback (event_id, user_id, rating, feedback_text)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE rating = VALUES(rating), feedback_text = VALUES(feedback_text), submitted_at = CURRENT_TIMESTAMP
        ");
        $stmt->bind_param("iiis", $event_id, $user_id, $rating, $feedback_text);
        if ($stmt->execute()) {
            $message = "Feedback submitted successfully.";
        } else {
            $message = "Failed to submit feedback. Please try again.";
        }
        $stmt->close();
    }
}

// ==========================
// Fetch existing feedback (if any)
// ==========================
$feedback_stmt = $conn->prepare("
    SELECT rating, feedback_text
    FROM event_feedback
    WHERE event_id = ? AND user_id = ?
");
$feedback_stmt->bind_param("ii", $event_id, $user_id);
$feedback_stmt->execute();
$feedback_res = $feedback_stmt->get_result();
$existing_feedback = $feedback_res->fetch_assoc();
$feedback_stmt->close();

// ==========================
// Fetch Event Details
// ==========================
$event_stmt = $conn->prepare("SELECT title FROM events WHERE event_id = ?");
$event_stmt->bind_param("i", $event_id);
$event_stmt->execute();
$event_res = $event_stmt->get_result();
$event = $event_res->fetch_assoc();
$event_stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Feedback | <?= htmlspecialchars($event['title']); ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container mt-5">
        <div class="card mx-auto" style="max-width: 500px;">
        <div class="card-body">
    <h3>Feedback for Event: <?= htmlspecialchars($event['title']); ?></h3>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST" class="mt-4">
        <div class="form-group">
            <label>Rating (1 to 5)</label>
            <select name="rating" class="form-control" required>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?= $i; ?>" <?= isset($existing_feedback['rating']) && $existing_feedback['rating'] == $i ? 'selected' : ''; ?>>
                        <?= $i; ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Feedback (optional)</label>
            <textarea name="feedback_text" class="form-control" rows="4"><?= htmlspecialchars($existing_feedback['feedback_text'] ?? ''); ?></textarea>
        </div>

        <button class="btn btn-success">Submit Feedback</button>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </form>
</div>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
