<?php
session_start();
include('config.php');

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Event Organizer') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = mysqli_prepare($conn, "DELETE FROM events WHERE event_id=? AND organizer_id=?");
    mysqli_stmt_bind_param($stmt, "ii", $delete_id, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: view_events.php");
    exit;
}

// Fetch events with optional search
$search = $_GET['search'] ?? '';
$search_sql = "%$search%";
$stmt = mysqli_prepare($conn, "SELECT e.event_id, e.title, e.start_datetime, e.end_datetime, e.status, c.name AS category 
                              FROM events e 
                              LEFT JOIN event_categories c ON e.category_id=c.id 
                              WHERE e.organizer_id=? AND e.title LIKE ? 
                              ORDER BY e.start_datetime DESC");
mysqli_stmt_bind_param($stmt, "is", $user_id, $search_sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$events = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Events</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <script>
        function confirmDelete(event_id) {
            if(confirm("Are you sure you want to delete this event?")) {
                window.location.href = "view_events.php?delete_id=" + event_id;
            }
        }
    </script>
</head>
<body>
<?php include('navbar.php'); ?>
    <div id="content">

<div class="container mt-5">
    <div >
        <h3>Your Events</h3>
        <a href="add_events.php" class="float-right btn btn-primary">Add New Event</a>
    </div>

    <form method="GET" class="form-inline mb-3">
        <input type="text" name="search" class="form-control mr-2" placeholder="Search by title" value="<?= htmlspecialchars($search); ?>">
        <button class="btn btn-secondary">Search</button>
        <a href="view_events.php" class="btn btn-outline-secondary ml-2">Reset</a>
    </form>

    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Start</th>
                <th>End</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($events) > 0): ?>
                <?php foreach($events as $event): ?>
                    <tr>
                        <td><?= htmlspecialchars($event['title']); ?></td>
                        <td><?= htmlspecialchars($event['category'] ?? ''); ?></td>
                        <td><?= $event['start_datetime']; ?></td>
                        <td><?= $event['end_datetime']; ?></td>
                        <td><?= ucfirst($event['status']); ?></td>
                        <td>
                            <a href="edit_event.php?id=<?= $event['event_id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                            <button onclick="confirmDelete(<?= $event['event_id']; ?>)" class="btn btn-sm btn-danger">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" class="text-center">No events found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
