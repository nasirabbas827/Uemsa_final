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
    $name = trim($_POST['resource_name'] ?? '');
    $quantity = intval($_POST['total_quantity'] ?? 0);

    if ($action === 'add' && $name !== '') {
        $stmt = $conn->prepare("INSERT INTO resources (resource_name, total_quantity) VALUES (?, ?)");
        $stmt->bind_param("si", $name, $quantity);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'edit' && !empty($_POST['id'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("UPDATE resources SET resource_name=?, total_quantity=? WHERE id=?");
        $stmt->bind_param("sii", $name, $quantity, $id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'delete' && !empty($_POST['id'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM resources WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
    // Refresh to update table
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch resources
$result = $conn->query("SELECT * FROM resources ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Resources | Admin</title>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('admin_navbar.php'); ?>

<div class="container mt-5">

<div class="container mt-5">
    <div  >
        <h3>Resources</h3>
        <button class="float-right mb-3 btn btn-success" data-toggle="modal" data-target="#addModal">Add Resource</button>
    </div>

    <table class="table table-bordered table-hover">
        <thead class="thead-dark">
            <tr>
                <th>#</th>
                <th>Resource Name</th>
                <th>Total Quantity</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id']; ?></td>
                <td><?= htmlspecialchars($row['resource_name']); ?></td>
                <td><?= $row['total_quantity']; ?></td>
                <td>
                    <button class="btn btn-primary btn-sm" 
                            data-toggle="modal" 
                            data-target="#editModal" 
                            data-id="<?= $row['id']; ?>" 
                            data-name="<?= htmlspecialchars($row['resource_name']); ?>"
                            data-quantity="<?= $row['total_quantity']; ?>">Edit</button>
                    
                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Delete this resource?');">
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
<div class="modal fade" id="addModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Add Resource</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div class="form-group">
                <label>Resource Name</label>
                <input type="text" name="resource_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Total Quantity</label>
                <input type="number" name="total_quantity" class="form-control" min="0" required>
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
<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <form method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit-id">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Edit Resource</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div class="form-group">
                <label>Resource Name</label>
                <input type="text" name="resource_name" id="edit-name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Total Quantity</label>
                <input type="number" name="total_quantity" id="edit-quantity" class="form-control" min="0" required>
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
    $('#editModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var name = button.data('name');
        var quantity = button.data('quantity');
        var modal = $(this);
        modal.find('#edit-id').val(id);
        modal.find('#edit-name').val(name);
        modal.find('#edit-quantity').val(quantity);
    });
</script>

</body>
</html>
