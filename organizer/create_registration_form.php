<?php
session_start();
include('config.php');

// =====================
// Auth check
// =====================
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Event Organizer') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

// =====================
// Fetch organizer's events
// =====================
$events = [];

$eventQuery = mysqli_prepare(
    $conn,
    "SELECT event_id, title 
     FROM events 
     WHERE organizer_id = ? 
     ORDER BY start_datetime DESC"
);

mysqli_stmt_bind_param($eventQuery, "i", $user_id);
mysqli_stmt_execute($eventQuery);
$result = mysqli_stmt_get_result($eventQuery);

while ($row = mysqli_fetch_assoc($result)) {
    $events[] = $row;
}

mysqli_stmt_close($eventQuery);

// =====================
// Handle form submit
// =====================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $event_id = intval($_POST['event_id']);
    $labels   = $_POST['field_label'];
    $types    = $_POST['field_type'];
    $required = isset($_POST['is_required']) ? $_POST['is_required'] : [];

    // Safety: ensure event belongs to organizer
    $check = mysqli_prepare(
        $conn,
        "SELECT event_id FROM events WHERE event_id = ? AND organizer_id = ?"
    );
    mysqli_stmt_bind_param($check, "ii", $event_id, $user_id);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);

    if (mysqli_stmt_num_rows($check) === 0) {
        $message = '<div class="alert alert-danger">Invalid event selected.</div>';
    } else {

        mysqli_stmt_close($check);

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO registration_forms 
             (event_id, field_label, field_type, is_required)
             VALUES (?, ?, ?, ?)"
        );

        foreach ($labels as $index => $label) {

            if (trim($label) === '') {
                continue;
            }

            $type        = $types[$index];
            $is_required = in_array($index, $required) ? 1 : 0;

            mysqli_stmt_bind_param(
                $stmt,
                "issi",
                $event_id,
                $label,
                $type,
                $is_required
            );

            mysqli_stmt_execute($stmt);
        }

        mysqli_stmt_close($stmt);

        $message = '<div class="alert alert-success">Registration form created successfully.</div>';
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Registration Form</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>
    <div id="content">


<div class="container mt-5">
    <div class="card mx-auto" style="max-width:900px;">
        <div class="card-body">

            <h4 class="text-center mb-4">Create Event Registration Form</h4>
            <?= $message; ?>

            <form method="POST">

                <!-- Event Selection -->
                <div class="form-group">
                    <label>Select Event</label>
                    <select name="event_id" class="form-control" required>
                        <option value="">-- Select Event --</option>
                        <?php foreach ($events as $event): ?>
                            <option value="<?= $event['event_id']; ?>">
                                <?= htmlspecialchars($event['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <hr>

                <h5>Form Fields</h5>

                <div id="fields-container">

                    <div class="border p-3 mb-3">
                        <div class="form-row">
                            <div class="col-md-5">
                                <input type="text"
                                       name="field_label[]"
                                       class="form-control"
                                       placeholder="Field Label"
                                       required>
                            </div>

                            <div class="col-md-4">
                                <select name="field_type[]" class="form-control">
                                    <option value="text">Text</option>
                                    <option value="textarea">Textarea</option>
                                    <option value="select">Select</option>
                                    <option value="checkbox">Checkbox</option>
                                    <option value="radio">Radio</option>
                                </select>
                            </div>

                            <div class="col-md-3 mt-2">
                                <label>
                                    <input type="checkbox" name="is_required[]" value="0">
                                    Required
                                </label>
                            </div>
                        </div>
                    </div>

                </div>

                <button type="button"
                        class="btn btn-outline-secondary mb-3"
                        onclick="addField()">
                    + Add Another Field
                </button>

                <div class="text-center">
                    <button class="btn btn-primary px-4">
                        Save Registration Form
                    </button>
                    <a href="view_registration_forms.php" class="btn btn-secondary px-4">View Form</a>

                </div>

            </form>

        </div>
    </div>
</div>

<script>
function addField() {
    const container = document.getElementById('fields-container');
    const index = container.children.length;

    container.insertAdjacentHTML('beforeend', `
        <div class="border p-3 mb-3">
            <div class="form-row">
                <div class="col-md-5">
                    <input type="text"
                           name="field_label[]"
                           class="form-control"
                           placeholder="Field Label"
                           required>
                </div>

                <div class="col-md-4">
                    <select name="field_type[]" class="form-control">
                        <option value="text">Text</option>
                        <option value="textarea">Textarea</option>
                        <option value="select">Select</option>
                        <option value="checkbox">Checkbox</option>
                        <option value="radio">Radio</option>
                    </select>
                </div>

                <div class="col-md-3 mt-2">
                    <label>
                        <input type="checkbox"
                               name="is_required[]"
                               value="${index}">
                        Required
                    </label>
                </div>
            </div>
        </div>
    `);
}
</script>

</body>
</html>
