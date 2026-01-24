<?php
session_start();
include('config.php');

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Event Organizer') {
    header("Location: ../login.php");
    exit;
}


$organizer_id = $_SESSION['user_id'];

// ==========================
// Handle sending a reply
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && isset($_POST['event_id'])) {
    $event_id = intval($_POST['event_id']);
    $message = trim($_POST['message']);

    if ($message !== '') {
        // Send message to participant
        $receiver_id = intval($_POST['receiver_id']);
        $stmt = $conn->prepare("INSERT INTO event_messages (event_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $event_id, $organizer_id, $receiver_id, $message);
        $stmt->execute();
        $stmt->close();
    }
}

// ==========================
// Fetch all events for this organizer
// ==========================
$events_stmt = $conn->prepare("SELECT event_id, title FROM events WHERE organizer_id=? ORDER BY start_datetime DESC");
$events_stmt->bind_param("i", $organizer_id);
$events_stmt->execute();
$events_res = $events_stmt->get_result();
$events = $events_res->fetch_all(MYSQLI_ASSOC);
$events_stmt->close();

// ==========================
// Fetch messages for these events
// ==========================
$event_ids = array_column($events, 'event_id');
$messages = [];

if (!empty($event_ids)) {
    $placeholders = implode(',', array_fill(0, count($event_ids), '?'));
    $types = str_repeat('i', count($event_ids));

    $stmt = $conn->prepare("
        SELECT m.*, u.first_name AS sender_name, u.id AS sender_user_id, e.title AS event_title
        FROM event_messages m
        JOIN users u ON m.sender_id = u.id
        JOIN events e ON m.event_id = e.event_id
        WHERE m.event_id IN ($placeholders)
        ORDER BY m.sent_at ASC
    ");

    $stmt->bind_param($types, ...$event_ids);
    $stmt->execute();
    $res = $stmt->get_result();
    $messages = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Organizer Messages</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>
    <div id="content">

<div class="container mt-5">

    <h3 class="mb-4">Messages from Participants</h3>

    <?php if (empty($messages)): ?>
        <div class="alert alert-info">No messages yet for your events.</div>
    <?php else: ?>
        <?php foreach ($messages as $msg): ?>
            <div class="card mb-3">
                <div class="card-header bg-secondary text-white">
                    Event: <?= htmlspecialchars($msg['event_title']); ?> | From: <?= htmlspecialchars($msg['sender_name']); ?> | Sent: <?= $msg['sent_at']; ?>
                </div>
                <div class="card-body">
                    <p><?= nl2br(htmlspecialchars($msg['message'])); ?></p>

                    <!-- Reply form -->
                    <?php if ($msg['sender_user_id'] !== $organizer_id): ?>
                    <form method="POST" class="mt-3">
                        <input type="hidden" name="event_id" value="<?= $msg['event_id']; ?>">
                        <input type="hidden" name="receiver_id" value="<?= $msg['sender_user_id']; ?>">
                        <div class="input-group">
                            <input type="text" name="message" class="form-control" placeholder="Type your reply..." required>
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary">Reply</button>
                            </div>
                        </div>
                    </form>
                    <?php endif; ?>

                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="text-center mb-5">
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
