<?php
session_start();
include('config.php');

// =====================
// Auth check
// =====================
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header("Location: ../login.php");
    exit;
}

$user_id  = $_SESSION['user_id'];
$event_id = intval($_GET['event_id'] ?? 0);
$message  = "";

// =====================
// Fetch event (approved + deadline check)
// =====================
$eventStmt = mysqli_prepare($conn,
    "SELECT event_id, title, registration_deadline
     FROM events
     WHERE event_id = ? AND status = 'approved'"
);
mysqli_stmt_bind_param($eventStmt, "i", $event_id);
mysqli_stmt_execute($eventStmt);
$eventResult = mysqli_stmt_get_result($eventStmt);
$event = mysqli_fetch_assoc($eventResult);
mysqli_stmt_close($eventStmt);

if (!$event) {
    die("Invalid or unavailable event.");
}

// Deadline check
$now = date('Y-m-d H:i:s');
if (!empty($event['registration_deadline']) && $event['registration_deadline'] < $now) {
    die("Registration deadline has passed.");
}

// =====================
// Prevent duplicate registration
// =====================
$checkStmt = mysqli_prepare($conn,
    "SELECT id FROM event_registrations
     WHERE event_id = ? AND user_id = ?"
);
mysqli_stmt_bind_param($checkStmt, "ii", $event_id, $user_id);
mysqli_stmt_execute($checkStmt);
mysqli_stmt_store_result($checkStmt);

if (mysqli_stmt_num_rows($checkStmt) > 0) {
    die("You are already registered for this event.");
}
mysqli_stmt_close($checkStmt);

// =====================
// Fetch registration fields
// =====================
$fields = [];
$fieldStmt = mysqli_prepare($conn,
    "SELECT id, field_label, field_type, is_required
     FROM registration_forms
     WHERE event_id = ?
     ORDER BY id ASC"
);
mysqli_stmt_bind_param($fieldStmt, "i", $event_id);
mysqli_stmt_execute($fieldStmt);
$result = mysqli_stmt_get_result($fieldStmt);

while ($row = mysqli_fetch_assoc($result)) {
    $fields[] = $row;
}
mysqli_stmt_close($fieldStmt);

// =====================
// Handle Submit
// =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Create registration
    $qr_token = bin2hex(random_bytes(32));

    $regStmt = mysqli_prepare($conn,
        "INSERT INTO event_registrations (event_id, user_id, qr_code_token)
         VALUES (?, ?, ?)"
    );
    mysqli_stmt_bind_param($regStmt, "iis", $event_id, $user_id, $qr_token);
    mysqli_stmt_execute($regStmt);
    $registration_id = mysqli_insert_id($conn);
    mysqli_stmt_close($regStmt);

    // Save responses
    $respStmt = mysqli_prepare($conn,
        "INSERT INTO registration_responses
         (registration_id, field_id, response_value)
         VALUES (?, ?, ?)"
    );

    foreach ($fields as $field) {
        $field_id = $field['id'];
        $value = $_POST['field_' . $field_id] ?? '';

        if (is_array($value)) {
            $value = implode(', ', $value);
        }

        mysqli_stmt_bind_param($respStmt, "iis",
            $registration_id,
            $field_id,
            $value
        );
        mysqli_stmt_execute($respStmt);
    }

    mysqli_stmt_close($respStmt);

    header("Location: dashboard.php?registered=1");
    exit;
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Event Registration</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container mt-5 mb-5">
    <div class="card mx-auto shadow" style="max-width:900px;">
        <div class="card-body">

            <h4 class="mb-4 text-center font-weight-bold">
                Register for: <?= htmlspecialchars($event['title']); ?>
            </h4>

            <form method="POST">

                <div class="row">
                    <?php foreach ($fields as $field): ?>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    <?= htmlspecialchars($field['field_label']); ?>
                                    <?php if ($field['is_required']): ?>
                                        <span class="text-danger">*</span>
                                    <?php endif; ?>
                                </label>

                                <?php if ($field['field_type'] === 'text'): ?>
                                    <input type="text"
                                           name="field_<?= $field['id']; ?>"
                                           class="form-control"
                                           <?= $field['is_required'] ? 'required' : ''; ?>>

                                <?php elseif ($field['field_type'] === 'textarea'): ?>
                                    <textarea name="field_<?= $field['id']; ?>"
                                              class="form-control"
                                              rows="3"
                                              <?= $field['is_required'] ? 'required' : ''; ?>></textarea>

                                <?php elseif ($field['field_type'] === 'select'): ?>
                                    <select name="field_<?= $field['id']; ?>"
                                            class="form-control"
                                            <?= $field['is_required'] ? 'required' : ''; ?>>
                                        <option value="">Select</option>
                                        <option value="Option 1">Option 1</option>
                                        <option value="Option 2">Option 2</option>
                                    </select>

                                <?php elseif ($field['field_type'] === 'checkbox'): ?>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               name="field_<?= $field['id']; ?>[]"
                                               value="Yes">
                                        <label class="form-check-label">
                                            Yes
                                        </label>
                                    </div>

                                <?php elseif ($field['field_type'] === 'radio'): ?>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input"
                                               type="radio"
                                               name="field_<?= $field['id']; ?>"
                                               value="Yes"
                                               <?= $field['is_required'] ? 'required' : ''; ?>>
                                        <label class="form-check-label">
                                            Yes
                                        </label>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <hr>

                <div class="text-center">
                    <button type="submit" class="btn btn-success px-5">
                        Submit Registration
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary px-5 ml-2">
                        Cancel
                    </a>
                </div>

            </form>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
