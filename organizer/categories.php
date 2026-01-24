<?php
session_start();
include('../config.php');

// Authentication Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION["role"]) || $_SESSION["role"] !== "Event Organizer") {
    header("Location: ../login.php");
    exit;
}

// Handle CRUD operations
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $name = trim($_POST['name']);

        if ($action === 'add' && !empty($name)) {
            $stmt = $conn->prepare("INSERT INTO event_categories (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'edit' && !empty($name) && !empty($_POST['id'])) {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("UPDATE event_categories SET name=? WHERE id=?");
            $stmt->bind_param("si", $name, $id);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'delete' && !empty($_POST['id'])) {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM event_categories WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
        // Refresh page to show updated list
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
}

// Fetch categories
$result = $conn->query("SELECT * FROM event_categories ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Categories | Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>
    <div id="content">

<div class="container mt-5">
    <div class="">
        <h3>Event Categories</h3>
        <button class="btn btn-success" data-toggle="modal" data-target="#addModal">Add Category</button>
    </div>

    <table class="table table-bordered table-hover">
        <thead class="thead-dark">
            <tr>
                <th>#</th>
                <th>Category Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id']; ?></td>
                <td><?= htmlspecialchars($row['name']); ?></td>
                <td>
                    <button class="btn btn-primary btn-sm" 
                            data-toggle="modal" 
                            data-target="#editModal" 
                            data-id="<?= $row['id']; ?>" 
                            data-name="<?= htmlspecialchars($row['name']); ?>">Edit</button>
                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Delete this category?');">
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

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="addModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addModalLabel">Add Category</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div class="form-group">
                <label>Category Name</label>
                <input type="text" name="name" class="form-control" required>
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

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit-id">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editModalLabel">Edit Category</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div class="form-group">
                <label>Category Name</label>
                <input type="text" name="name" id="edit-name" class="form-control" required>
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
    // Populate Edit Modal with category data
    $('#editModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget)
        var id = button.data('id')
        var name = button.data('name')
        var modal = $(this)
        modal.find('#edit-id').val(id)
        modal.find('#edit-name').val(name)
    })
</script>

</body>
</html>
