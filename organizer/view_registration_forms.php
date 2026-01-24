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

    $stmt = mysqli_prepare($conn,
        "DELETE rf FROM registration_forms rf
         JOIN events e ON rf.event_id = e.event_id
         WHERE rf.id = ? AND e.organizer_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "ii", $delete_id, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: view_registration_forms.php");
    exit;
}

// Search
$search = $_GET['search'] ?? '';
$search_sql = "%$search%";

// Fetch registration forms
$stmt = mysqli_prepare($conn,
    "SELECT rf.id, rf.field_label, rf.field_type, rf.is_required,
            e.title AS event_title
     FROM registration_forms rf
     JOIN events e ON rf.event_id = e.event_id
     WHERE e.organizer_id = ? AND e.title LIKE ?
     ORDER BY e.start_datetime DESC"
);

mysqli_stmt_bind_param($stmt, "is", $user_id, $search_sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$forms = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Registration Forms</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <script>
        function confirmDelete(id) {
            if (confirm("Are you sure you want to delete this form field?")) {
                window.location.href = "view_registration_forms.php?delete_id=" + id;
            }
        }
    </script>
</head>
<body>

<?php include('navbar.php'); ?>
    <div id="content">

<div class="container mt-5">

    <div class="mb-3">
        <h3>Registration Forms</h3>
        <a href="create_registration_form.php" class="float-right btn btn-primary">
            Create Registration Form
        </a>
    </div>

    <form method="GET" class="form-inline mb-3">
        <input type="text"
               name="search"
               class="form-control mr-2"
               placeholder="Search by Event Title"
               value="<?= htmlspecialchars($search); ?>">
        <button class="btn btn-secondary">Search</button>
        <a href="view_registration_forms.php" class="btn btn-outline-secondary ml-2">Reset</a>
    </form>

    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>Event</th>
                <th>Field Label</th>
                <th>Field Type</th>
                <th>Required</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>

        <?php if (count($forms) > 0): ?>
            <?php foreach ($forms as $form): ?>
                <tr>
                    <td><?= htmlspecialchars($form['event_title']); ?></td>
                    <td><?= htmlspecialchars($form['field_label']); ?></td>
                    <td><?= ucfirst($form['field_type']); ?></td>
                    <td><?= $form['is_required'] ? 'Yes' : 'No'; ?></td>
                    <td>
                        <button onclick="confirmDelete(<?= $form['id']; ?>)"
                                class="btn btn-sm btn-danger">
                            Delete
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" class="text-center">No registration forms found.</td>
            </tr>
        <?php endif; ?>

        </tbody>
    </table>

</div>

</body>
</html>
