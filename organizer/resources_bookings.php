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

// 1. Handle Resource Allocation (Booking)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_resource'])) {
    $event_id = intval($_POST['event_id']);
    $resource_id = intval($_POST['resource_id']);
    $quantity = intval($_POST['quantity']);

    // Check if event belongs to organizer
    $stmt = $conn->prepare("SELECT COUNT(*) FROM events WHERE event_id=? AND organizer_id=?");
    $stmt->bind_param("ii", $event_id, $organizer_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count == 0) {
        $message = '<div class="alert alert-danger alert-dismissible fade show">You can only book resources for your own events! <button type="button" class="close" data-dismiss="alert">&times;</button></div>';
    } else {
        // Availability Check
        $stmt2 = $conn->prepare("
            SELECT r.total_quantity, IFNULL(SUM(er.quantity_allocated),0) AS allocated 
            FROM resources r
            LEFT JOIN event_resources er ON r.id=er.resource_id AND er.status != 'cancelled'
            WHERE r.id=? GROUP BY r.id");
        $stmt2->bind_param("i", $resource_id);
        $stmt2->execute();
        $stmt2->bind_result($total, $booked);
        $stmt2->fetch();
        $stmt2->close();

        if ($quantity + $booked > $total) {
            $message = '<div class="alert alert-warning alert-dismissible fade show">Not enough stock! Only '.($total - $booked).' units left. <button type="button" class="close" data-dismiss="alert">&times;</button></div>';
        } else {
            $stmt3 = $conn->prepare("INSERT INTO event_resources (event_id, resource_id, quantity_allocated, status) VALUES (?,?,?,'pending')");
            $stmt3->bind_param("iii", $event_id, $resource_id, $quantity);
            if ($stmt3->execute()) {
                $message = '<div class="alert alert-success alert-dismissible fade show">Booking request sent successfully! <button type="button" class="close" data-dismiss="alert">&times;</button></div>';
            }
            $stmt3->close();
        }
    }
}

// 2. Search & Fetch Resources
$search = $_GET['search'] ?? "";
$search_query = "%$search%";
$res_sql = "SELECT r.*, 
            (r.total_quantity - IFNULL((SELECT SUM(er.quantity_allocated) FROM event_resources er WHERE er.resource_id = r.id AND er.status != 'cancelled'), 0)) as available
            FROM resources r 
            WHERE r.resource_name LIKE ? ORDER BY r.resource_name ASC";
$stmt = $conn->prepare($res_sql);
$stmt->bind_param("s", $search_query);
$stmt->execute();
$resources = $stmt->get_result();

// 3. Fetch Organizer Events for Modal
$events = $conn->query("SELECT event_id, title FROM events WHERE organizer_id=$organizer_id ORDER BY title ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resource Marketplace | Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<link rel="stylesheet" href="../css/style.css">
    <style>
        .section-title { font-weight: 600; color: #333; margin-bottom: 25px; border-left: 5px solid #28a745; padding-left: 15px; }
        .resource-card { transition: 0.3s; border: none; border-radius: 15px; }
        .resource-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); }
        .search-bar { border-radius: 50px; padding: 25px 30px; border: none; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .btn-round { border-radius: 50px; padding: 10px 25px; font-weight: 600; }
        .stock-indicator { font-size: 0.8rem; font-weight: 600; text-transform: uppercase; }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>
    <div id="content">

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title mb-0">Resource Marketplace</h2>
        <a href="my_resources_bookings.php" class="btn btn-outline-success btn-round">View My Bookings</a>
    </div>

    <?= $message; ?>

    <div class="row mb-5">
        <div class="col-md-8 mx-auto">
            <form method="GET" class="input-group">
                <input type="text" name="search" class="form-control search-bar" placeholder="Search resources (e.g. Chairs, Projector, Sound System)..." value="<?= htmlspecialchars($search) ?>">
                <div class="input-group-append">
                    <button class="btn btn-success px-4" style="border-radius: 0 50px 50px 0;">Search</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <?php if($resources->num_rows > 0): ?>
            <?php while($r = $resources->fetch_assoc()): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card resource-card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="font-weight-bold mb-1"><?= htmlspecialchars($r['resource_name']) ?></h5>
                            <p class="text-muted small mb-3">Resource ID: #RES-<?= $r['id'] ?></p>
                            
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div>
                                    <span class="stock-indicator text-muted d-block">Available</span>
                                    <span class="h4 font-weight-bold <?= $r['available'] > 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= $r['available'] ?>
                                    </span> 
                                    <span class="text-muted">/ <?= $r['total_quantity'] ?></span>
                                </div>
                                <div class="text-right">
                                    <?php if($r['available'] > 0): ?>
                                        <span class="badge badge-success px-3 py-2">In Stock</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger px-3 py-2">Out of Stock</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <button class="btn btn-dark btn-block btn-round" 
                                    data-toggle="modal" data-target="#bookModal"
                                    data-id="<?= $r['id'] ?>" 
                                    data-name="<?= htmlspecialchars($r['resource_name']) ?>"
                                    data-max="<?= $r['available'] ?>"
                                    <?= $r['available'] <= 0 ? 'disabled' : '' ?>>
                                <?= $r['available'] > 0 ? 'Request Booking' : 'Fully Allocated' ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <h4 class="text-muted">No resources found matching your search.</h4>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="bookModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form method="POST" class="modal-content border-0" style="border-radius: 15px;">
            <input type="hidden" name="book_resource" value="1">
            <input type="hidden" name="resource_id" id="modal-res-id">
            
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title font-weight-bold">Book <span id="modal-res-name"></span></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            
            <div class="modal-body p-4">
                <div class="form-group">
                    <label class="font-weight-bold small">Assign to Event</label>
                    <select name="event_id" class="form-control custom-select" required>
                        <option value="">Select one of your events...</option>
                        <?php $events->data_seek(0); while($e = $events->fetch_assoc()): ?>
                            <option value="<?= $e['event_id'] ?>"><?= htmlspecialchars($e['title']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="font-weight-bold small">Required Quantity</label>
                    <input type="number" name="quantity" id="modal-qty" class="form-control" min="1" required>
                    <small class="text-muted">Maximum available: <span id="modal-max-label"></span></small>
                </div>
            </div>
            
            <div class="modal-footer border-0 px-4 pb-4">
                <button type="button" class="btn btn-light btn-round" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success btn-round px-4">Confirm Request</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    $('#bookModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var name = button.data('name');
        var max = button.data('max');

        var modal = $(this);
        modal.find('#modal-res-id').val(id);
        modal.find('#modal-res-name').text(name);
        modal.find('#modal-max-label').text(max);
        modal.find('#modal-qty').attr('max', max);
    });
</script>
</body>
</html>