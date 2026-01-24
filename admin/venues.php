<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// Handle CRUD operations
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $capacity = intval($_POST['capacity'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($action === 'add' && $name !== '') {
        $stmt = $conn->prepare("INSERT INTO venues (name, location, capacity, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $name, $location, $capacity, $description);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'edit' && !empty($_POST['id'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("UPDATE venues SET name=?, location=?, capacity=?, description=? WHERE id=?");
        $stmt->bind_param("ssisi", $name, $location, $capacity, $description, $id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'delete' && !empty($_POST['id'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM venues WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Fetch all venues
$result = $conn->query("SELECT * FROM venues ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Venues | Admin Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('admin_navbar.php'); ?>

<div class="container mt-5">
    <div  >
        <h3>Venues</h3>
        <button class="btn btn-success float-right mb-3" data-toggle="modal" data-target="#addModal">Add Venue</button>
    </div>

    <table class="table table-bordered table-hover">
        <thead class="thead-dark">
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Location</th>
                <th>Capacity</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id']; ?></td>
                <td><?= htmlspecialchars($row['name']); ?></td>
                <td><?= htmlspecialchars($row['location']); ?></td>
                <td><?= $row['capacity']; ?></td>
                <td><?= htmlspecialchars($row['description']); ?></td>
                <td>
                    <button class="btn btn-primary btn-sm" 
                            data-toggle="modal" 
                            data-target="#editModal" 
                            data-id="<?= $row['id']; ?>" 
                            data-name="<?= htmlspecialchars($row['name']); ?>" 
                            data-location="<?= htmlspecialchars($row['location']); ?>"
                            data-capacity="<?= $row['capacity']; ?>"
                            data-description="<?= htmlspecialchars($row['description']); ?>">Edit</button>
                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Delete this venue?');">
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

<!-- Add Venue Modal -->
<div class="modal fade" id="addModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Add Venue</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div class="form-group">
                <label>Venue Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" class="form-control">
            </div>
            <div class="form-group">
                <label>Capacity</label>
                <input type="number" name="capacity" class="form-control" min="0">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control"></textarea>
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

<!-- Edit Venue Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <form method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit-id">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Edit Venue</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div class="form-group">
                <label>Venue Name</label>
                <input type="text" name="name" id="edit-name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" id="edit-location" class="form-control">
            </div>
            <div class="form-group">
                <label>Capacity</label>
                <input type="number" name="capacity" id="edit-capacity" class="form-control" min="0">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="edit-description" class="form-control"></textarea>
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
        $('#edit-name').val(button.data('name'));
        $('#edit-location').val(button.data('location'));
        $('#edit-capacity').val(button.data('capacity'));
        $('#edit-description').val(button.data('description'));
    });
</script>

</body>
</html>
