<?php
session_start();
include('config.php');

// ────────────────────────────────────────────────
//  Authentication – only admin
// ────────────────────────────────────────────────
// Admin authentication
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}


// ────────────────────────────────────────────────
//  Statistics – system-wide overview
// ────────────────────────────────────────────────

// 1. Total users by role
$users_by_role = ['Student' => 0, 'Faculty' => 0, 'Event Organizer' => 0, 'admin' => 0];
$result = $conn->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role");
while ($row = $result->fetch_assoc()) {
    $users_by_role[$row['role']] = $row['cnt'];
}
$total_users = array_sum($users_by_role);

// 2. Events – total + by status
$events_status = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'cancelled' => 0, 'completed' => 0];
$result = $conn->query("SELECT status, COUNT(*) as cnt FROM events GROUP BY status");
while ($row = $result->fetch_assoc()) {
    $events_status[$row['status']] = $row['cnt'];
}
$total_events = array_sum($events_status);

// 3. Registrations – total + attended
$total_registrations = $conn->query("SELECT COUNT(*) FROM event_registrations")->fetch_row()[0] ?? 0;
$total_attended      = $conn->query("SELECT COUNT(*) FROM event_attendance")->fetch_row()[0] ?? 0;

// 4. Average event rating (system-wide)
$avg_rating_query = $conn->query("SELECT ROUND(AVG(rating), 1) as avg FROM event_feedback");
$avg_rating = $avg_rating_query->fetch_assoc()['avg'] ?? 0;

// 5. Pending approvals (events + venue bookings + resources)
$pending_events       = $conn->query("SELECT COUNT(*) FROM events WHERE status = 'pending'")->fetch_row()[0] ?? 0;
$pending_venue_book   = $conn->query("SELECT COUNT(*) FROM venue_bookings WHERE status = 'pending'")->fetch_row()[0] ?? 0;
$pending_resource_req = $conn->query("SELECT COUNT(*) FROM event_resources WHERE status = 'pending'")->fetch_row()[0] ?? 0;
$total_pending_approvals = $pending_events + $pending_venue_book + $pending_resource_req;

// 6. Recent activity – last 10 events created
$recent_events = $conn->query("
    SELECT e.event_id, e.title, e.status, e.created_at, 
           u.first_name, u.last_name,
           c.name AS category
    FROM events e
    LEFT JOIN users u ON e.organizer_id = u.id
    LEFT JOIN event_categories c ON e.category_id = c.id
    ORDER BY e.created_at DESC LIMIT 10
");

// 7. Recent feedback – last 8
$recent_feedback = $conn->query("
    SELECT ef.rating, ef.feedback_text, ef.submitted_at,
           e.title AS event_title,
           CONCAT(u.first_name, ' ', u.last_name) AS student_name
    FROM event_feedback ef
    JOIN events e ON ef.event_id = e.event_id
    JOIN users u ON ef.user_id = u.id
    ORDER BY ef.submitted_at DESC LIMIT 8
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – UEMS</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <link rel="stylesheet" href="../css/style.css">

    <style>
        body { background: #f5f7fa; }
        .welcome-card {
            background: linear-gradient(135deg, #4e54c8, #8f94fb);
            color: white;
            border-radius: 15px;
        }
        .stat-card {
            transition: transform 0.25s, box-shadow 0.25s;
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        .stat-card:hover { transform: translateY(-6px); box-shadow: 0 12px 30px rgba(0,0,0,0.15); }
        .icon-bg {
            position: absolute;
            top: -20px; right: 20px;
            font-size: 3rem;
            opacity: 0.15;
            color: white;
        }
        .chart-container { height: 320px; position: relative; }
        .list-group-item { border-left: 4px solid transparent; }
        .list-group-item.pending   { border-left-color: #ffc107; }
        .list-group-item.approved  { border-left-color: #28a745; }
        .list-group-item.rejected  { border-left-color: #dc3545; }
    </style>
</head>
<body>

<?php include('admin_navbar.php'); ?>

<div class="container-fluid mt-4 pb-5">

    <!-- Welcome -->
    <div class="card welcome-card mb-5 shadow-lg text-center py-5">
        <div class="card-body">
            <h2 class="font-weight-bold mb-2">UEMS Administration Panel</h2>
            <p class="lead mb-1">Welcome back, <strong>Admin</strong></p>
            <p class="mb-0">Manage events, users, venues, resources and system settings</p>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-5">

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-primary text-white">
                <i class="fas fa-users icon-bg"></i>
                <div class="card-body">
                    <h5>Total Users</h5>
                    <h3 class="mb-0"><?= number_format($total_users) ?></h3>
                    <small><?= $users_by_role['Student'] ?> students • <?= $users_by_role['Event Organizer'] ?> organizers</small>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-success text-white">
                <i class="fas fa-calendar-check icon-bg"></i>
                <div class="card-body">
                    <h5>Total Events</h5>
                    <h3 class="mb-0"><?= number_format($total_events) ?></h3>
                    <small><?= $events_status['approved'] ?> approved • <?= $events_status['pending'] ?> pending</small>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-info text-white">
                <i class="fas fa-ticket-alt icon-bg"></i>
                <div class="card-body">
                    <h5>Registrations</h5>
                    <h3 class="mb-0"><?= number_format($total_registrations) ?></h3>
                    <small><?= number_format($total_attended) ?> attended</small>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-warning text-dark">
                <i class="fas fa-tasks icon-bg"></i>
                <div class="card-body">
                    <h5>Pending Actions</h5>
                    <h3 class="mb-0"><?= number_format($total_pending_approvals) ?></h3>
                    <small><?= $pending_events ?> events • <?= $pending_venue_book ?> venues</small>
                </div>
            </div>
        </div>

    </div>

    <!-- Charts Row -->
    <div class="row mb-5">

        <!-- Events by status -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Events by Status</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="eventsStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Average rating + feedback count -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Feedback Overview</h5>
                </div>
                <div class="card-body text-center">
                    <h1 class="display-4 font-weight-bold"><?= $avg_rating ?></h1>
                    <p class="lead">Average Event Rating</p>
                    <hr>
                    <h5>Feedback received</h5>
                    <h3><?= $conn->query("SELECT COUNT(*) FROM event_feedback")->fetch_row()[0] ?? 0 ?></h3>
                </div>
            </div>
        </div>

        <!-- Users by role -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">User Distribution</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="usersRoleChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Recent Activity – two columns -->
    <div class="row">

        <!-- Recent Events -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Events</h5>
                    <a href="admin_view_events.php" class="btn btn-sm btn-light">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if ($recent_events->num_rows > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php while ($e = $recent_events->fetch_assoc()): ?>
                                <li class="list-group-item list-group-item-<?= $e['status'] ?>">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong><?= htmlspecialchars($e['title']) ?></strong><br>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name']) ?> •
                                                <?= $e['category'] ?: 'No category' ?>
                                            </small>
                                        </div>
                                        <span class="badge badge-<?= 
                                            $e['status'] === 'approved' ? 'success' : 
                                            ($e['status'] === 'pending' ? 'warning' : 
                                            ($e['status'] === 'rejected' ? 'danger' : 'secondary'))
                                        ?>">
                                            <?= ucfirst($e['status']) ?>
                                        </span>
                                    </div>
                                    <small class="text-muted"><?= date('d M Y • H:i', strtotime($e['created_at'])) ?></small>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">No events created yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Feedback -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Feedback</h5>
                    <a href="manage_feedback.php" class="btn btn-sm btn-light">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if ($recent_feedback->num_rows > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php while ($f = $recent_feedback->fetch_assoc()): ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <strong><?= htmlspecialchars($f['event_title']) ?></strong>
                                        <span class="badge badge-pill badge-warning"><?= $f['rating'] ?> ★</span>
                                    </div>
                                    <div class="small text-muted mt-1">
                                        <?= htmlspecialchars($f['student_name']) ?> • 
                                        <?= date('d M Y H:i', strtotime($f['submitted_at'])) ?>
                                    </div>
                                    <div class="mt-2 small">
                                        <?= htmlspecialchars(substr($f['feedback_text'] ?: 'No comment', 0, 120)) ?>...
                                    </div>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">No feedback received yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
// Events status chart
new Chart(document.getElementById('eventsStatusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Approved', 'Rejected', 'Cancelled', 'Completed'],
        datasets: [{
            data: [<?= $events_status['pending'] ?>, <?= $events_status['approved'] ?>, <?= $events_status['rejected'] ?>, <?= $events_status['cancelled'] ?>, <?= $events_status['completed'] ?>],
            backgroundColor: ['#ffc107', '#28a745', '#dc3545', '#6c757d', '#17a2b8']
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, legend: { position: 'bottom' } }
});

// Users by role chart
new Chart(document.getElementById('usersRoleChart'), {
    type: 'pie',
    data: {
        labels: ['Students', 'Faculty', 'Organizers', 'Admins'],
        datasets: [{
            data: [<?= $users_by_role['Student'] ?>, <?= $users_by_role['Faculty'] ?>, <?= $users_by_role['Event Organizer'] ?>, <?= $users_by_role['admin'] ?>],
            backgroundColor: ['#007bff', '#6610f2', '#fd7e14', '#dc3545']
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, legend: { position: 'bottom' } }
});
</script>

</body>
</html>