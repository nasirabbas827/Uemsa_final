<?php
session_start();
include('../config.php');

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Event Organizer') {
    header("Location: ../login.php");
    exit;
}

$organizer_id = $_SESSION['user_id'];
$message = "";

// Handle delete
if (isset($_GET['delete'])) {
    $booking_id = intval($_GET['delete']);
    // Ensure organizer owns the booking
    $stmt = $conn->prepare("SELECT COUNT(*) FROM venue_bookings vb JOIN events e ON vb.event_id=e.event_id WHERE vb.id=? AND e.organizer_id=? AND vb.status='pending'");
    $stmt->bind_param("ii", $booking_id, $organizer_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $stmt = $conn->prepare("DELETE FROM venue_bookings WHERE id=?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $stmt->close();
        $message = '<div class="alert alert-success">Booking deleted successfully.</div>';
    } else {
        $message = '<div class="alert alert-danger">Cannot delete. Only pending bookings can be deleted.</div>';
    }
}

// Handle edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_booking'])) {
    $booking_id = intval($_POST['booking_id']);
    $venue_id = intval($_POST['venue_id']);
    $event_id = intval($_POST['event_id']);
    $booking_date = $_POST['booking_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    // Check ownership & status
    $stmt = $conn->prepare("SELECT COUNT(*) FROM venue_bookings vb JOIN events e ON vb.event_id=e.event_id WHERE vb.id=? AND e.organizer_id=? AND vb.status='pending'");
    $stmt->bind_param("ii", $booking_id, $organizer_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count == 0) {
        $message = '<div class="alert alert-danger">Cannot edit. Only your pending bookings can be edited.</div>';
    } else {
        // Conflict check
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM venue_bookings
            WHERE venue_id=? AND booking_date=? AND id<>? 
            AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?))
        ");
        $stmt->bind_param("isissss", $venue_id, $booking_date, $booking_id, $start_time, $start_time, $end_time, $end_time);
        $stmt->execute();
        $stmt->bind_result($conflict);
        $stmt->fetch();
        $stmt->close();

        if ($conflict > 0) {
            $message = '<div class="alert alert-warning">Booking conflict! Venue already booked during this time.</div>';
        } else {
            $stmt = $conn->prepare("UPDATE venue_bookings SET venue_id=?, event_id=?, booking_date=?, start_time=?, end_time=? WHERE id=?");
            $stmt->bind_param("iisssi", $venue_id, $event_id, $booking_date, $start_time, $end_time, $booking_id);
            $stmt->execute();
            $stmt->close();
            $message = '<div class="alert alert-success">Booking updated successfully.</div>';
        }
    }
}

// Fetch organizer bookings
$bookings = $conn->query("
    SELECT vb.id, e.title AS event_title, v.name AS venue_name, vb.booking_date, vb.start_time, vb.end_time, vb.status, vb.venue_id, vb.event_id
    FROM venue_bookings vb
    JOIN events e ON vb.event_id=e.event_id
    JOIN venues v ON vb.venue_id=v.id
    WHERE e.organizer_id=$organizer_id
    ORDER BY vb.booking_date DESC, vb.start_time ASC
");

// Fetch organizer events and all venues for modal selects
$events = $conn->query("SELECT event_id, title FROM events WHERE organizer_id=$organizer_id ORDER BY title");
$venues = $conn->query("SELECT id, name FROM venues ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Bookings | Dashboard</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>
    <div id="content">

<div class="container mt-5">
    <h3>My Venue Bookings</h3>
    <?php echo $message; ?>

    <table class="table table-bordered table-hover">
        <thead class="thead-dark">
            <tr>
                <th>#</th>
                <th>Event</th>
                <th>Venue</th>
                <th>Date</th>
                <th>Start</th>
                <th>End</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $bookings->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id']; ?></td>
                <td><?= htmlspecialchars($row['event_title']); ?></td>
                <td><?= htmlspecialchars($row['venue_name']); ?></td>
                <td><?= $row['booking_date']; ?></td>
                <td><?= $row['start_time']; ?></td>
                <td><?= $row['end_time']; ?></td>
                <td><?= ucfirst($row['status']); ?></td>
                <td>
                    <?php if($row['status']=='pending'): ?>
                        <button class="btn btn-primary btn-sm" data-toggle="modal" 
                                data-target="#editModal" 
                                data-id="<?= $row['id']; ?>" 
                                data-venue="<?= $row['venue_id']; ?>" 
                                data-event="<?= $row['event_id']; ?>" 
                                data-date="<?= $row['booking_date']; ?>" 
                                data-start="<?= $row['start_time']; ?>" 
                                data-end="<?= $row['end_time']; ?>">Edit</button>
                        <a href="?delete=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this booking?');">Delete</a>
                    <?php else: ?>
                        <span class="text-muted">Locked</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Edit Booking Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <form method="POST">
        <input type="hidden" name="edit_booking" value="1">
        <input type="hidden" name="booking_id" id="edit-booking-id">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Booking</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Event</label>
                    <select name="event_id" id="edit-event-id" class="form-control" required>
                        <?php
                        $events->data_seek(0);
                        while($e = $events->fetch_assoc()):
                        ?>
                            <option value="<?= $e['event_id']; ?>"><?= htmlspecialchars($e['title']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Venue</label>
                    <select name="venue_id" id="edit-venue-id" class="form-control" required>
                        <?php
                        $venues->data_seek(0);
                        while($v = $venues->fetch_assoc()):
                        ?>
                            <option value="<?= $v['id']; ?>"><?= htmlspecialchars($v['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Booking Date</label>
                    <input type="date" name="booking_date" id="edit-date" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Start Time</label>
                        <input type="time" name="start_time" id="edit-start" class="form-control" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>End Time</label>
                        <input type="time" name="end_time" id="edit-end" class="form-control" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary">Update</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
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
    $('#edit-booking-id').val(button.data('id'));
    $('#edit-event-id').val(button.data('event'));
    $('#edit-venue-id').val(button.data('venue'));
    $('#edit-date').val(button.data('date'));
    $('#edit-start').val(button.data('start'));
    $('#edit-end').val(button.data('end'));
});
</script>

</body>
</html>
