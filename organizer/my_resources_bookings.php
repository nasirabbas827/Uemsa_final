<?php
session_start();
include('config.php');

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Event Organizer') {
    header("Location: ../login.php");
    exit;
}

$organizer_id = $_SESSION['user_id'];
$message = "";

// --- HANDLE DELETE ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("
        DELETE er FROM event_resources er
        JOIN events e ON er.event_id=e.event_id
        WHERE er.id=? AND e.organizer_id=? AND er.status='pending'
    ");
    $stmt->bind_param("ii", $id, $organizer_id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $message = '<div class="alert alert-success alert-dismissible fade show">Allocation deleted. <button type="button" class="close" data-dismiss="alert">&times;</button></div>';
    } else {
        $message = '<div class="alert alert-danger alert-dismissible fade show">Could not delete. (Only pending items can be removed). <button type="button" class="close" data-dismiss="alert">&times;</button></div>';
    }
    $stmt->close();
}

// --- HANDLE EDIT (Update Quantity) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_allocation'])) {
    $id = intval($_POST['allocation_id']);
    $new_qty = intval($_POST['quantity']);

    // Check availability and ownership
    $stmt = $conn->prepare("
        UPDATE event_resources er
        JOIN events e ON er.event_id = e.event_id
        SET er.quantity_allocated = ?
        WHERE er.id = ? AND e.organizer_id = ? AND er.status = 'pending'
    ");
    $stmt->bind_param("iii", $new_qty, $id, $organizer_id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $message = '<div class="alert alert-success alert-dismissible fade show">Allocation updated successfully. <button type="button" class="close" data-dismiss="alert">&times;</button></div>';
    } else {
        $message = '<div class="alert alert-warning alert-dismissible fade show">Update failed. Item may no longer be pending. <button type="button" class="close" data-dismiss="alert">&times;</button></div>';
    }
    $stmt->close();
}

// Fetch allocations
$allocations = $conn->query("
    SELECT er.id, e.title AS event_title, r.resource_name, er.quantity_allocated, r.total_quantity, er.status, er.resource_id
    FROM event_resources er
    JOIN events e ON er.event_id=e.event_id
    JOIN resources r ON er.resource_id=r.id
    WHERE e.organizer_id=$organizer_id
    ORDER BY er.id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Allocations | Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<link rel="stylesheet" href="../css/style.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        .section-title { font-weight: 600; color: #333; margin-bottom: 25px; border-left: 5px solid #007bff; padding-left: 15px; }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .table { background: white; border-radius: 15px; overflow: hidden; }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-confirmed { background: #d4edda; color: #155724; }
        .badge-cancelled { background: #f8d7da; color: #721c24; }
        .btn-action { border-radius: 50px; font-size: 0.85rem; padding: 5px 15px; }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>
    <div id="content">

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title mb-0">My Resource Allocations</h2>
        <a href="resources_bookings.php" class="btn btn-primary btn-action">+ Request More</a>
    </div>

    <?= $message; ?>

    <div class="table-responsive shadow-sm" style="border-radius: 15px;">
        <table class="table table-hover mb-0">
            <thead class="thead-dark">
                <tr>
                    <th>Event</th>
                    <th>Resource</th>
                    <th class="text-center">Qty</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $allocations->fetch_assoc()): ?>
                <tr>
                    <td class="font-weight-bold"><?= htmlspecialchars($row['event_title']); ?></td>
                    <td><?= htmlspecialchars($row['resource_name']); ?></td>
                    <td class="text-center"><span class="badge badge-light border"><?= $row['quantity_allocated']; ?></span></td>
                    <td class="text-center">
                        <?php 
                            $status_class = 'badge-' . $row['status'];
                            echo "<span class='status-badge $status_class'>" . ucfirst($row['status']) . "</span>";
                        ?>
                    </td>
                    <td class="text-center">
                        <?php if($row['status'] === 'pending'): ?>
                            <button class="btn btn-outline-info btn-sm btn-action mr-1" 
                                    onclick='openEditModal(<?= json_encode($row); ?>)'>Edit</button>
                            <a href="?delete=<?= $row['id']; ?>" class="btn btn-outline-danger btn-sm btn-action" 
                               onclick="return confirm('Delete this request?');">Delete</a>
                        <?php else: ?>
                            <small class="text-muted"><i class="fa fa-lock"></i> Locked</small>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form method="POST" class="modal-content border-0" style="border-radius: 15px;">
            <div class="modal-header bg-info text-white border-0">
                <h5 class="modal-title font-weight-bold">Edit Allocation</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="update_allocation" value="1">
                <input type="hidden" name="allocation_id" id="edit-id">
                
                <p class="mb-3">Updating request for: <strong id="edit-res-name"></strong></p>
                
                <div class="form-group">
                    <label class="font-weight-bold small">New Quantity</label>
                    <input type="number" name="quantity" id="edit-qty" class="form-control" min="1" required>
                    <small class="text-muted">Current Stock Available: <span id="edit-max-label"></span></small>
                </div>
            </div>
            <div class="modal-footer border-0 p-4">
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-info px-4" style="border-radius: 50px;">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function openEditModal(data) {
    $('#edit-id').val(data.id);
    $('#edit-res-name').text(data.resource_name);
    $('#edit-qty').val(data.quantity_allocated);
    $('#edit-max-label').text(data.total_quantity);
    $('#editModal').modal('show');
}
</script>

</body>
</html>