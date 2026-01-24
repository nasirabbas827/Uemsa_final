<?php
session_start();
include('config.php');

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Event Organizer') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

// Fetch categories
$categories = [];
$result = mysqli_query($conn, "SELECT id, name FROM event_categories ORDER BY name ASC");
while($row = mysqli_fetch_assoc($result)) $categories[] = $row;

// Fetch event
$event_id = intval($_GET['id'] ?? 0);
$stmt = mysqli_prepare($conn, "SELECT * FROM events WHERE event_id=? AND organizer_id=?");
mysqli_stmt_bind_param($stmt, "ii", $event_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$event = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$event) {
    die("Event not found.");
}

// Handle update
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : NULL;
    $event_type = $_POST['event_type'];
    $start_datetime = $_POST['start_datetime'];
    $end_datetime = $_POST['end_datetime'];
    $capacity = intval($_POST['capacity']);
    $registration_deadline = !empty($_POST['registration_deadline']) ? $_POST['registration_deadline'] : NULL;

    // Banner image
    $banner_image = $event['banner_image'];
    if (!empty($_FILES['banner_image']['name'])) {
        $target_dir = "../images/";
        $banner_image = basename($_FILES["banner_image"]["name"]);
        move_uploaded_file($_FILES["banner_image"]["tmp_name"], $target_dir . $banner_image);
    }

    $stmt = mysqli_prepare($conn, "UPDATE events SET title=?, description=?, category_id=?, event_type=?, start_datetime=?, end_datetime=?, capacity=?, registration_deadline=?, banner_image=? WHERE event_id=? AND organizer_id=?");
    mysqli_stmt_bind_param($stmt, "ssisssssssi", $title, $description, $category_id, $event_type, $start_datetime, $end_datetime, $capacity, $registration_deadline, $banner_image, $event_id, $user_id);

    if (mysqli_stmt_execute($stmt)) {
        $message = '<div class="alert alert-success">Event updated successfully</div>';
        header("Refresh:1; url=view_events.php");
    } else {
        $message = '<div class="alert alert-danger">Update failed</div>';
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Event</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
    <div id="content">

<div class="container mt-5">
    <div class="card mx-auto" style="max-width:800px;">
        <div class="card-body">
            <h4 class="text-center mb-4">Edit Event</h4>
            <?= $message ?>

            <form method="POST" enctype="multipart/form-data">

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($event['title']); ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Category</label>
                        <select name="category_id" class="form-control">
                            <option value="">Select Category</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat['id']; ?>" <?= $cat['id'] == $event['category_id'] ? 'selected':''; ?>><?= htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($event['description']); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Event Type</label>
                        <select name="event_type" class="form-control">
                            <option value="one_time" <?= $event['event_type']=='one_time'?'selected':''; ?>>One Time</option>
                            <option value="recurring" <?= $event['event_type']=='recurring'?'selected':''; ?>>Recurring</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Capacity</label>
                        <input type="number" name="capacity" class="form-control" value="<?= $event['capacity']; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Start Date & Time</label>
                        <input type="datetime-local" name="start_datetime" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($event['start_datetime'])); ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>End Date & Time</label>
                        <input type="datetime-local" name="end_datetime" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($event['end_datetime'])); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Registration Deadline (Optional)</label>
                    <input type="datetime-local" name="registration_deadline" class="form-control" value="<?= $event['registration_deadline'] ? date('Y-m-d\TH:i', strtotime($event['registration_deadline'])) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Banner Image (Optional)</label>
                    <input type="file" name="banner_image" class="form-control-file">
                    <?php if($event['banner_image']): ?>
                        <img src="../images/<?= htmlspecialchars($event['banner_image']); ?>" class="img-thumbnail mt-2" style="max-width:200px;">
                    <?php endif; ?>
                </div>

                <div class="text-center mt-3">
                    <button class="btn btn-primary px-4">Update Event</button>
                    <a href="view_events.php" class="btn btn-secondary px-4">Back</a>
                </div>

            </form>
        </div>
    </div>
</div>
</body>
</html>
