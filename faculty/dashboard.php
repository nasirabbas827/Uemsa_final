<?php
session_start();
include('config.php');

// ==========================
// Authentication Check
// ==========================
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Faculty') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch full name from DB (reliable)
$stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ? AND role = 'Faculty'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $name = trim($row['first_name'] . ' ' . $row['last_name']);
} else {
    $name = "Faculty";
}
$stmt->close();

// Optional: cache in session
if (!isset($_SESSION['full_name'])) {
    $_SESSION['full_name'] = $name;
}

// ==========================
// Quick Stats
// ==========================

// 1. Upcoming registered events
$stmt = $conn->prepare("
    SELECT COUNT(*) as count
    FROM event_registrations er
    JOIN events e ON er.event_id = e.event_id
    WHERE er.user_id = ? 
    AND e.start_datetime > NOW()
    AND er.status IN ('confirmed', 'pending')
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$upcoming_registered = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
$stmt->close();

// 2. Events I've attended
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT er.event_id) as count
    FROM event_registrations er
    JOIN event_attendance ea ON ea.registration_id = er.id
    WHERE er.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$attended = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
$stmt->close();

// 3. Pending feedback (attended but not yet given feedback)
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT er.event_id) as count
    FROM event_registrations er
    JOIN event_attendance ea ON ea.registration_id = er.id
    LEFT JOIN event_feedback ef ON ef.event_id = er.event_id AND ef.user_id = er.user_id
    WHERE er.user_id = ? 
    AND ea.attended_at IS NOT NULL 
    AND ef.id IS NULL
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_feedback = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
$stmt->close();

// ==========================
// Fetch Categories (for filter)
// ==========================
$categories = [];
$catResult = $conn->query("SELECT id, name FROM event_categories ORDER BY name ASC");
while ($row = $catResult->fetch_assoc()) {
    $categories[] = $row;
}

// ==========================
// Filters for upcoming events
// ==========================
$search     = $_GET['search'] ?? '';
$category   = $_GET['category'] ?? '';
$from_date  = $_GET['from_date'] ?? date('Y-m-d'); // default today
$to_date    = $_GET['to_date'] ?? '';

// ==========================
// Upcoming Events Query
// ==========================
$sql = "
    SELECT 
        e.event_id,
        e.title,
        e.description,
        e.start_datetime,
        e.end_datetime,
        e.registration_deadline,
        e.banner_image,
        c.name AS category,
        er.status AS reg_status,
        er.id AS registration_id,
        er.qr_code_token
    FROM events e
    LEFT JOIN event_categories c ON e.category_id = c.id
    LEFT JOIN event_registrations er ON er.event_id = e.event_id AND er.user_id = ?
    WHERE e.status = 'approved'
      AND e.start_datetime > NOW()
";

$params = [$user_id];
$types  = "i";

if ($search !== '') {
    $sql .= " AND e.title LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

if ($category !== '') {
    $sql .= " AND e.category_id = ?";
    $params[] = $category;
    $types .= "i";
}

if ($from_date !== '') {
    $sql .= " AND e.start_datetime >= ?";
    $params[] = "$from_date 00:00:00";
    $types .= "s";
}

if ($to_date !== '') {
    $sql .= " AND e.start_datetime <= ?";
    $params[] = "$to_date 23:59:59";
    $types .= "s";
}

$sql .= " ORDER BY e.start_datetime ASC LIMIT 12";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$events_result = $stmt->get_result();
$events = $events_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Close connection at the end
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard | UEMS</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .stat-card { transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
        .event-card { min-height: 380px; }
        .event-img { height: 160px; object-fit: cover; }
    </style>
</head>
<body class="bg-light">

<?php include('navbar.php'); ?>

<div class="container-fluid mt-4 pb-5">

    <!-- Welcome Header -->
    <div class="card mb-4 shadow-lg text-white text-center py-4" style="background: linear-gradient(135deg, #28a745, #1e7e34);">
        <div class="card-body">
            <h2 class="mb-2">Faculty Dashboard</h2>
            <p class="lead mb-0">Welcome back, <strong><?= htmlspecialchars($name) ?></strong>!</p>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-5">
        <div class="col-md-4 mb-4">
            <div class="card stat-card shadow border-success">
                <div class="card-body text-center">
                    <i class="fas fa-calendar-check fa-3x text-success mb-3"></i>
                    <h5>Upcoming Registered</h5>
                    <h3 class="font-weight-bold"><?= $upcoming_registered ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card stat-card shadow border-info">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-3x text-info mb-3"></i>
                    <h5>Events Attended</h5>
                    <h3 class="font-weight-bold"><?= $attended ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card stat-card shadow border-warning">
                <div class="card-body text-center">
                    <i class="fas fa-comment-dots fa-3x text-warning mb-3"></i>
                    <h5>Pending Feedback</h5>
                    <h3 class="font-weight-bold"><?= $pending_feedback ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" class="form-row align-items-end">
                <div class="col-md-3 mb-2">
                    <input type="text" name="search" class="form-control" placeholder="Search event title..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2 mb-2">
                    <select name="category" class="form-control">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($category == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($from_date) ?>">
                </div>
                <div class="col-md-2 mb-2">
                    <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($to_date) ?>">
                </div>
                <div class="col-md-3 mb-2">
                    <button type="submit" class="btn btn-primary mr-2"><i class="fas fa-filter"></i> Filter</button>
                    <a href="dashboard.php" class="btn btn-outline-secondary"><i class="fas fa-redo"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Upcoming Events -->
    <h4 class="mb-3"><i class="fas fa-calendar-alt mr-2"></i>Upcoming Events</h4>
    <?php if (count($events) > 0): ?>
        <div class="row">
            <?php foreach ($events as $event): ?>
                <?php
                $now = date('Y-m-d H:i:s');
                $deadline_passed = $event['registration_deadline'] && $event['registration_deadline'] < $now;
                $is_registered = !empty($event['reg_status']);
                $can_cancel = $is_registered && in_array($event['reg_status'], ['pending','confirmed']) && $event['start_datetime'] > date('Y-m-d H:i:s', strtotime('+24 hours'));
                ?>

                <div class="col-md-4 col-lg-3 mb-4">
                    <div class="card event-card shadow-sm h-100">
                        <?php if (!empty($event['banner_image'])): ?>
                            <img src="../images/<?= htmlspecialchars($event['banner_image']) ?>" class="card-img-top event-img" alt="Event banner">
                        <?php else: ?>
                            <div class="bg-light text-center py-5 event-img"><i class="fas fa-image fa-3x text-muted"></i></div>
                        <?php endif; ?>

                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($event['title']) ?></h5>
                            <p class="text-muted small mb-2"><?= htmlspecialchars($event['category'] ?? 'General') ?></p>
                            <p class="small mb-2">
                                <strong><i class="far fa-clock"></i> Start:</strong><br>
                                <?= date('d M Y • h:i A', strtotime($event['start_datetime'])) ?>
                            </p>

                            <?php if ($is_registered): ?>
                                <span class="badge badge-pill badge-success mb-2">
                                    <?= ucfirst($event['reg_status']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="card-footer bg-white text-center">
                            <?php if (!$is_registered && !$deadline_passed): ?>
                                <a href="event_register.php?event_id=<?= $event['event_id'] ?>" class="btn btn-success btn-sm btn-block mb-2">
                                    <i class="fas fa-sign-in-alt"></i> Register
                                </a>
                            <?php elseif ($deadline_passed): ?>
                                <button class="btn btn-secondary btn-sm btn-block mb-2" disabled>Registration Closed</button>
                            <?php endif; ?>

                            <div class="btn-group btn-group-sm w-100">
                                <a href="event_details.php?event_id=<?= $event['event_id'] ?>" class="btn btn-outline-info">
                                    <i class="fas fa-info-circle"></i> Details
                                </a>
                                <a href="event_feedback.php?event_id=<?= $event['event_id'] ?>" class="btn btn-outline-info">
                                    <i class="fas fa-info-circle"></i> Feedback
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            No upcoming events match your filters.
        </div>
    <?php endif; ?>

</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>