<?php
session_start();
include('config.php');

// ==========================
// Auth check
// ==========================
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Faculty') {
    header("Location: ../login.php");
    exit;
}

$event_id = intval($_GET['event_id'] ?? 0);
if (!$event_id) {
    die("Invalid Event ID");
}

$user_id = $_SESSION['user_id'];

// ==========================
// Handle sending a message
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && trim($_POST['message']) !== '') {
    $message = trim($_POST['message']);

    // Get organizer of event
    $event_stmt = $conn->prepare("SELECT organizer_id FROM events WHERE event_id=?");
    $event_stmt->bind_param("i", $event_id);
    $event_stmt->execute();
    $ev_res = $event_stmt->get_result()->fetch_assoc();
    $receiver_id = $ev_res['organizer_id'];
    $event_stmt->close();

    $stmt = $conn->prepare("INSERT INTO event_messages (event_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $event_id, $user_id, $receiver_id, $message);
    $stmt->execute();
    $stmt->close();
}

// ==========================
// Fetch Event Details
// ==========================
$sql = "
SELECT 
    e.*,
    c.name AS category_name,
    u.first_name AS organizer_first,
    u.last_name AS organizer_last
FROM events e
LEFT JOIN event_categories c ON e.category_id = c.id
LEFT JOIN users u ON e.organizer_id = u.id
WHERE e.event_id = ? AND e.status='approved'
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();
if (!$event) die("Event not found or not approved");

// ==========================
// Fetch Venue Booking
// ==========================
$venue_sql = "
SELECT v.name, v.location, vb.booking_date, vb.start_time, vb.end_time
FROM venue_bookings vb
JOIN venues v ON vb.venue_id = v.id
WHERE vb.event_id = ? AND vb.status='confirmed'
";
$venue_stmt = $conn->prepare($venue_sql);
$venue_stmt->bind_param("i", $event_id);
$venue_stmt->execute();
$venue_res = $venue_stmt->get_result();
$venue = $venue_res->fetch_assoc();
$venue_stmt->close();

// ==========================
// Fetch Resources
// ==========================
$res_sql = "
SELECT r.resource_name, er.quantity_allocated, er.status
FROM event_resources er
JOIN resources r ON er.resource_id = r.id
WHERE er.event_id = ?
";
$res_stmt = $conn->prepare($res_sql);
$res_stmt->bind_param("i", $event_id);
$res_stmt->execute();
$res_result = $res_stmt->get_result();
$resources = $res_result->fetch_all(MYSQLI_ASSOC);
$res_stmt->close();

// ==========================
// Fetch Media
// ==========================
$media_sql = "
SELECT file, file_type
FROM event_media
WHERE event_id = ?
";
$media_stmt = $conn->prepare($media_sql);
$media_stmt->bind_param("i", $event_id);
$media_stmt->execute();
$media_res = $media_stmt->get_result();
$media_files = $media_res->fetch_all(MYSQLI_ASSOC);
$media_stmt->close();

// ==========================
// Fetch Messages
// ==========================
$msg_stmt = $conn->prepare("
SELECT m.*, u.first_name AS sender_name
FROM event_messages m
JOIN users u ON m.sender_id = u.id
WHERE m.event_id=?
ORDER BY m.sent_at ASC
");
$msg_stmt->bind_param("i", $event_id);
$msg_stmt->execute();
$msg_res = $msg_stmt->get_result();
$messages = $msg_res->fetch_all(MYSQLI_ASSOC);
$msg_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Details | <?= htmlspecialchars($event['title']); ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container mt-5">

    <!-- Event Details Card -->
    <div class="card shadow mb-4">
        <?php if (!empty($event['banner_image'])): ?>
            <img src="../images/<?= htmlspecialchars($event['banner_image']); ?>" class="card-img-top" style="height:300px; object-fit:cover;">
        <?php endif; ?>
        <div class="card-body">
            <h3 class="card-title"><?= htmlspecialchars($event['title']); ?></h3>
            <p class="text-muted mb-2">
                <strong>Category:</strong> <?= htmlspecialchars($event['category_name'] ?? 'N/A'); ?><br>
                <strong>Organizer:</strong> <?= htmlspecialchars($event['organizer_first'].' '.$event['organizer_last']); ?><br>
                <strong>Type:</strong> <?= ucfirst($event['event_type']); ?><br>
                <strong>Status:</strong> <?= ucfirst($event['status']); ?><br>
                <strong>Capacity:</strong> <?= intval($event['capacity']); ?>
            </p>
            <p>
                <strong>Start:</strong> <?= $event['start_datetime']; ?><br>
                <strong>End:</strong> <?= $event['end_datetime']; ?><br>
                <strong>Registration Deadline:</strong> <?= $event['registration_deadline'] ?? 'N/A'; ?>
            </p>
            <p><?= nl2br(htmlspecialchars($event['description'])); ?></p>
        </div>
    </div>

    <!-- Venue -->
    <?php if ($venue): ?>
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white">Venue Details</div>
        <div class="card-body">
            <p>
                <strong>Name:</strong> <?= htmlspecialchars($venue['name']); ?><br>
                <strong>Location:</strong> <?= htmlspecialchars($venue['location']); ?><br>
                <strong>Booking Date:</strong> <?= $venue['booking_date']; ?><br>
                <strong>Start:</strong> <?= $venue['start_time']; ?><br>
                <strong>End:</strong> <?= $venue['end_time']; ?>
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Resources -->
    <?php if (count($resources) > 0): ?>
    <div class="card shadow mb-4">
        <div class="card-header bg-success text-white">Allocated Resources</div>
        <div class="card-body">
            <ul>
                <?php foreach ($resources as $res): ?>
                <li><?= htmlspecialchars($res['resource_name']); ?> - Quantity: <?= intval($res['quantity_allocated']); ?> (<?= ucfirst($res['status']); ?>)</li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <!-- Media -->
    <?php if (count($media_files) > 0): ?>
    <div class="card shadow mb-4">
        <div class="card-header bg-info text-white">Event Media</div>
        <div class="card-body">
            <?php foreach ($media_files as $media): ?>
                <?php if ($media['file_type'] === 'image'): ?>
                    <img src="../images/<?= htmlspecialchars($media['file']); ?>" class="img-fluid mb-2" style="max-height:200px;">
                <?php else: ?>
                    <p><a href="../images/<?= htmlspecialchars($media['file']); ?>" target="_blank">View File: <?= htmlspecialchars($media['file']); ?></a></p>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Messages Section -->
    <div class="card shadow mb-4">
        <div class="card-header bg-warning text-dark">Event Messages</div>
        <div class="card-body" style="max-height:300px; overflow-y:auto;" id="message-box">
            <?php if (count($messages) > 0): ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="<?= $msg['sender_id']==$user_id ? 'text-right' : 'text-left'; ?> mb-2">
                        <b><?= htmlspecialchars($msg['sender_name']); ?>:</b> <?= htmlspecialchars($msg['message']); ?>
                        <br><small class="text-muted"><?= $msg['sent_at']; ?></small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-muted">No messages yet.</p>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <form method="POST">
                <div class="input-group">
                    <input type="text" name="message" class="form-control" placeholder="Type your message..." required>
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-primary">Send</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="text-center mb-5">
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
