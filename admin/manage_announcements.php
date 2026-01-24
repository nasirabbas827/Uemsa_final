<?php
include('config.php');
session_start();

// ==========================
// Admin Authentication Check
// ==========================
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// ==========================
// Handle CRUD operations
// ==========================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $id = intval($_POST['id'] ?? 0);

    if ($action === 'add' && $title !== '') {
        $stmt = $conn->prepare("INSERT INTO announcements (title, message) VALUES (?, ?)");
        $stmt->bind_param("ss", $title, $message);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'edit' && $id > 0) {
        $stmt = $conn->prepare("UPDATE announcements SET title=?, message=? WHERE id=?");
        $stmt->bind_param("ssi", $title, $message, $id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'delete' && $id > 0) {
        $stmt = $conn->prepare("DELETE FROM announcements WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// ==========================
// Fetch all announcements
// ==========================
$result = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Announcements | Admin Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>

<?php include('admin_navbar.php'); ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Announcements</h3>
        <button class="btn btn-success" data-toggle="modal" data-target="#addModal">Add Announcement</button>
    </div>

    <table class="table table-bordered table-hover">
        <thead class="thead-dark">
            <tr>
                <th>#</th>
                <th>Title</th>
                <th>Message</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id']; ?></td>
                    <td><?= htmlspecialchars($row['title']); ?></td>
                    <td><?= nl2br(htmlspecialchars($row['message'])); ?></td>
                    <td><?= date("d M Y, h:i A", strtotime($row['created_at'])); ?></td>
                    <td>
                        <button class="btn btn-primary btn-sm" 
                                data-toggle="modal" 
                                data-target="#editModal" 
                                data-id="<?= $row['id']; ?>" 
                                data-title="<?= htmlspecialchars($row['title']); ?>" 
                                data-message="<?= htmlspecialchars($row['message']); ?>">
                            Edit
                        </button>
                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('Delete this announcement?');">
                            <input type="hidden" name="id" value="<?= $row['id']; ?>">
                            <input type="hidden" name="action" value="delete">
                            <button class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Add Announcement Modal -->
<div class="modal fade" id="addModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Add Announcement</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Message</label>
                <textarea name="message" class="form-control" rows="4"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success">Add</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          </div>
        </div>
    </form>
  </div>
</div>

<!-- Edit Announcement Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <form method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit-id">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Edit Announcement</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" id="edit-title" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Message</label>
                <textarea name="message" id="edit-message" class="form-control" rows="4"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Update</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          </div>
        </div>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Populate Edit Modal
    $('#editModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        $('#edit-id').val(button.data('id'));
        $('#edit-title').val(button.data('title'));
        $('#edit-message').val(button.data('message'));
    });
</script>

</body>
</html>
