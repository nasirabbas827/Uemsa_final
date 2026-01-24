<?php
session_start();
include('config.php');

// Authentication Check 
if (!isset($_SESSION['user_id']) || !isset($_SESSION["role"]) || $_SESSION["role"] !== "Event Organizer") {
    header("Location: ../login.php");
    exit;
}

$organizer_id = $_SESSION['user_id'];
// Fetch user info from database
$stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ? AND role = 'Event Organizer'");
$stmt->bind_param("i", $organizer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $first_name = $row['first_name'];
    $last_name  = $row['last_name'] ?? '';
    $name = trim($first_name . ' ' . $last_name);
} else {
    // Fallback in case user not found (shouldn't happen after login)
    $name = "Organizer";
}

// Optional: Store in session for future pages (so next time no query needed)
$_SESSION['full_name'] = $name;

// ────────────────────────────────────────────────
//  Fetch Analytics Data
// ────────────────────────────────────────────────

// 1. Total events by status
$stmt = $conn->prepare("
    SELECT 
        status, 
        COUNT(*) as count 
    FROM events 
    WHERE organizer_id = ? 
    GROUP BY status
");
$stmt->bind_param("i", $organizer_id);
$stmt->execute();
$status_result = $stmt->get_result();
$event_status = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'cancelled' => 0, 'completed' => 0];
while ($row = $status_result->fetch_assoc()) {
    $event_status[$row['status']] = $row['count'];
}
$total_events = array_sum($event_status);

// 2. Upcoming events (start_datetime > NOW())
$stmt = $conn->prepare("
    SELECT COUNT(*) as upcoming 
    FROM events 
    WHERE organizer_id = ? 
    AND start_datetime > NOW() 
    AND status = 'approved'
");
$stmt->bind_param("i", $organizer_id);
$stmt->execute();
$upcoming = $stmt->get_result()->fetch_assoc()['upcoming'] ?? 0;

// 3. Total registrations & attended
$stmt = $conn->prepare("
    SELECT 
        COUNT(er.id) as total_reg,
        SUM(CASE WHEN ea.id IS NOT NULL THEN 1 ELSE 0 END) as attended
    FROM event_registrations er
    LEFT JOIN event_attendance ea ON ea.registration_id = er.id
    WHERE er.event_id IN (SELECT event_id FROM events WHERE organizer_id = ?)
");
$stmt->bind_param("i", $organizer_id);
$stmt->execute();
$reg_data = $stmt->get_result()->fetch_assoc();
$total_registrations = $reg_data['total_reg'] ?? 0;
$total_attended = $reg_data['attended'] ?? 0;

// 4. Average rating & feedback count
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as feedback_count,
        AVG(rating) as avg_rating
    FROM event_feedback ef
    WHERE ef.event_id IN (SELECT event_id FROM events WHERE organizer_id = ?)
");
$stmt->bind_param("i", $organizer_id);
$stmt->execute();
$feedback_data = $stmt->get_result()->fetch_assoc();
$avg_rating = round($feedback_data['avg_rating'] ?? 0, 1);
$feedback_count = $feedback_data['feedback_count'] ?? 0;

// 5. Registrations per month (last 6 months) for line chart
$stmt = $conn->prepare("
    SELECT DATE_FORMAT(er.registered_at, '%Y-%m') as month, COUNT(*) as count
    FROM event_registrations er
    WHERE er.event_id IN (SELECT event_id FROM events WHERE organizer_id = ?)
    AND er.registered_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month
");
$stmt->bind_param("i", $organizer_id);
$stmt->execute();
$reg_months = $stmt->get_result();
$months = [];
$reg_counts = [];
while ($row = $reg_months->fetch_assoc()) {
    $months[] = date('M Y', strtotime($row['month'] . '-01'));
    $reg_counts[] = $row['count'];
}

// 6. Feedback ratings distribution (for pie/bar chart)
$stmt = $conn->prepare("
    SELECT rating, COUNT(*) as count
    FROM event_feedback ef
    WHERE ef.event_id IN (SELECT event_id FROM events WHERE organizer_id = ?)
    GROUP BY rating
    ORDER BY rating
");
$stmt->bind_param("i", $organizer_id);
$stmt->execute();
$rating_dist = $stmt->get_result();
$ratings = [1=>0, 2=>0, 3=>0, 4=>0, 5=>0];
while ($row = $rating_dist->fetch_assoc()) {
    $ratings[$row['rating']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Organizer Dashboard | UEMS</title>
    
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    
    <!-- Custom CSS (add to your style.css or inline) -->
    <style>
        .welcome-card { background: linear-gradient(135deg, #007bff, #0056b3); }
        .stat-card { transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
        .chart-container { position: relative; height: 320px; width: 100%; }
    </style>
</head>
<body class="bg-light">

<?php include('navbar.php'); ?>

<div id="content" class="container-fluid mt-4 pb-5">

    <!-- Welcome Header -->
    <div class="card welcome-card mb-5 shadow-lg text-white text-center py-5">
        <div class="card-body">
            <h1 class="display-5 mb-2 font-weight-bold">Event Organizer Dashboard</h1>
            <p class="lead mb-0">Welcome back, <strong><?= htmlspecialchars($name); ?></strong>!</p>
        </div>
    </div>

    <!-- Quick Stats Row -->
    <div class="row mb-5">
        <div class="col-md-3 mb-4">
            <div class="card stat-card shadow border-primary">
                <div class="card-body text-center">
                    <h5 class="text-primary"><i class="fas fa-calendar-check fa-2x mb-2"></i><br>Total Events</h5>
                    <h3 class="font-weight-bold"><?= $total_events ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card stat-card shadow border-success">
                <div class="card-body text-center">
                    <h5 class="text-success"><i class="fas fa-clock fa-2x mb-2"></i><br>Upcoming</h5>
                    <h3 class="font-weight-bold"><?= $upcoming ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card stat-card shadow border-info">
                <div class="card-body text-center">
                    <h5 class="text-info"><i class="fas fa-users fa-2x mb-2"></i><br>Registrations</h5>
                    <h3 class="font-weight-bold"><?= $total_registrations ?></h3>
                    <small><?= $total_attended ?> attended</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card stat-card shadow border-warning">
                <div class="card-body text-center">
                    <h5 class="text-warning"><i class="fas fa-star fa-2x mb-2"></i><br>Avg. Rating</h5>
                    <h3 class="font-weight-bold"><?= $avg_rating ?> / 5</h3>
                    <small><?= $feedback_count ?> feedbacks</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-5">
        <!-- Event Status Pie Chart -->
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Events by Status</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feedback Rating Distribution -->
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Feedback Ratings</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="ratingChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Registrations Over Time -->
        <div class="col-12 mb-4">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Registrations (Last 6 Months)</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions & Export -->
    <div class="row mb-5">
        <div class="col-md-4 mb-3">
            <a href="add_events.php" class="btn btn-lg btn-success btn-block">
                <i class="fas fa-plus-circle mr-2"></i> Create New Event
            </a>
        </div>
        <div class="col-md-4 mb-3">
            <a href="view_events.php" class="btn btn-lg btn-primary btn-block">
                <i class="fas fa-list mr-2"></i> Manage My Events
            </a>
        </div>
        <div class="col-md-4 mb-3">
            <div class="btn-group btn-block">
                <button class="btn btn-lg btn-secondary dropdown-toggle" data-toggle="dropdown">
                    <i class="fas fa-download mr-2"></i> Export Reports
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="export.php?type=csv&data=events">Events (CSV)</a>
                    <a class="dropdown-item" href="export.php?type=csv&data=registrations">Registrations (CSV)</a>
                    <a class="dropdown-item" href="export.php?type=csv&data=feedback">Feedback (CSV)</a>
                    <!-- Add PDF/Excel later with libraries -->
                </div>
            </div>
        </div>
    </div>

</div> <!-- container-fluid -->

<!-- Chart.js Scripts -->
<script>
// Event Status Pie
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'pie',
    data: {
        labels: ['Pending', 'Approved', 'Rejected', 'Cancelled', 'Completed'],
        datasets: [{
            data: [<?= $event_status['pending'] ?>, <?= $event_status['approved'] ?>, <?= $event_status['rejected'] ?>, <?= $event_status['cancelled'] ?>, <?= $event_status['completed'] ?>],
            backgroundColor: ['#ffc107', '#28a745', '#dc3545', '#6c757d', '#17a2b8'],
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

// Rating Distribution Bar
const ratingCtx = document.getElementById('ratingChart').getContext('2d');
new Chart(ratingCtx, {
    type: 'bar',
    data: {
        labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
        datasets: [{
            label: 'Count',
            data: [<?= $ratings[1] ?>, <?= $ratings[2] ?>, <?= $ratings[3] ?>, <?= $ratings[4] ?>, <?= $ratings[5] ?>],
            backgroundColor: '#ff6b6b'
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
});

// Registrations Trend Line
const trendCtx = document.getElementById('trendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{
            label: 'Registrations',
            data: <?= json_encode($reg_counts) ?>,
            borderColor: '#0d6efd',
            tension: 0.3,
            fill: false
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});
</script>

<!-- Bootstrap JS & jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>