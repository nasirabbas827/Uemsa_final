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
while($row = mysqli_fetch_assoc($result)) {
    $categories[] = $row;
}

// Handle form submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : NULL;
    $event_type = $_POST['event_type'];
    $start_datetime = $_POST['start_datetime'];
    $end_datetime = $_POST['end_datetime'];
    $capacity = intval($_POST['capacity']);
    $registration_deadline = !empty($_POST['registration_deadline']) ? $_POST['registration_deadline'] : NULL;

    // Handle banner image
    $banner_image = NULL;
    if (!empty($_FILES['banner_image']['name'])) {
        $target_dir = "../images/";
        $banner_image = basename($_FILES["banner_image"]["name"]);
        move_uploaded_file($_FILES["banner_image"]["tmp_name"], $target_dir . $banner_image);
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO events 
        (title, description, organizer_id, category_id, event_type, start_datetime, end_datetime, capacity, registration_deadline, banner_image)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssssssssss", $title, $description, $user_id, $category_id, $event_type, $start_datetime, $end_datetime, $capacity, $registration_deadline, $banner_image);

    if (mysqli_stmt_execute($stmt)) {
        $message = '<div class="alert alert-success">Event added successfully</div>';
    } else {
        $message = '<div class="alert alert-danger">Failed to add event</div>';
    }

    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Event</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
    <div id="content">

<div class="container mt-5">
    <div class="card mx-auto" style="max-width:800px;">
        <div class="card-body">
            <h4 class="text-center mb-4">Add New Event</h4>
            <?php echo $message; ?>

            <form method="POST" enctype="multipart/form-data">

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Category</label>
                        <select name="category_id" class="form-control">
                            <option value="">Select Category</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3" required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Event Type</label>
                        <select name="event_type" class="form-control" required>
                            <option value="one_time">One Time</option>
                            <option value="recurring">Recurring</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Capacity</label>
                        <input type="number" name="capacity" class="form-control" placeholder="0 = Unlimited" value="0">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Start Date & Time</label>
                        <input type="datetime-local" name="start_datetime" class="form-control" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>End Date & Time</label>
                        <input type="datetime-local" name="end_datetime" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Registration Deadline (Optional)</label>
                    <input type="datetime-local" name="registration_deadline" class="form-control">
                </div>

                <div class="form-group">
                    <label>Banner Image (Optional)</label>
                    <input type="file" name="banner_image" class="form-control-file">
                </div>

                <div class="text-center mt-3">
                    <button class="btn btn-primary px-4">Add Event</button>
                    <a href="view_events.php" class="btn btn-secondary px-4">View Events</a>
                </div>

            </form>
        </div>
    </div>
</div>

</body>
</html>
