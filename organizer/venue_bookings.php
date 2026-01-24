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

// Handle Booking Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_venue'])) {
    $venue_id = intval($_POST['venue_id']);
    $event_id = intval($_POST['event_id']);
    $booking_date = $_POST['booking_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    $stmt = $conn->prepare("SELECT COUNT(*) FROM events WHERE event_id=? AND organizer_id=?");
    $stmt->bind_param("ii", $event_id, $organizer_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count == 0) {
        $message = '<div class="alert alert-danger alert-dismissible fade show">You can only book your own events. <button type="button" class="close" data-dismiss="alert">&times;</button></div>';
    } else {
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM venue_bookings
            WHERE venue_id=? AND booking_date=? 
            AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?))
        ");
        $stmt->bind_param("isssss", $venue_id, $booking_date, $start_time, $start_time, $end_time, $end_time);
        $stmt->execute();
        $stmt->bind_result($conflict);
        $stmt->fetch();
        $stmt->close();

        if ($conflict > 0) {
            $message = '<div class="alert alert-warning alert-dismissible fade show"><strong>Conflict!</strong> Venue is already booked for this time. <button type="button" class="close" data-dismiss="alert">&times;</button></div>';
        } else {
            $stmt = $conn->prepare("INSERT INTO venue_bookings (venue_id,event_id,booking_date,start_time,end_time) VALUES (?,?,?,?,?)");
            $stmt->bind_param("iisss", $venue_id, $event_id, $booking_date, $start_time, $end_time);
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success alert-dismissible fade show">Booking confirmed! <button type="button" class="close" data-dismiss="alert">&times;</button></div>';
            } else {
                $message = '<div class="alert alert-danger alert-dismissible fade show">Booking failed. <button type="button" class="close" data-dismiss="alert">&times;</button></div>';
            }
            $stmt->close();
        }
    }
}

// Fetch venues
$search = $_GET['search'] ?? "";
if (!empty($search)) {
    $search_val = "%$search%";
    $venues_sql = "SELECT * FROM venues WHERE name LIKE ? OR location LIKE ? ORDER BY name";
    $stmt = $conn->prepare($venues_sql);
    $stmt->bind_param("ss", $search_val, $search_val);
    $stmt->execute();
    $venues = $stmt->get_result();
} else {
    $venues = $conn->query("SELECT * FROM venues ORDER BY name");
}

$events = $conn->query("SELECT event_id, title FROM events WHERE organizer_id=$organizer_id ORDER BY title");

// Calendar Events
$calendar_events = [];
$res = $conn->query("SELECT vb.id, e.title, vb.booking_date, vb.start_time, vb.end_time FROM venue_bookings vb JOIN events e ON vb.event_id = e.event_id WHERE e.organizer_id = $organizer_id");
while ($b = $res->fetch_assoc()) {
    $calendar_events[] = [
        'title' => $b['title'],
        'start' => $b['booking_date'].'T'.$b['start_time'],
        'end'   => $b['booking_date'].'T'.$b['end_time'],
        'backgroundColor' => '#007bff',
        'borderColor' => '#0056b3'
    ];
}
$json_events = json_encode($calendar_events);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Venue Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
    <link rel="stylesheet" href="../css/style.css">

    <style>
        .venue-card { 
            transition: transform 0.3s, box-shadow 0.3s; 
            border: none; 
            border-radius: 12px; 
            overflow: hidden; 
        }
        .venue-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 10px 20px rgba(0,0,0,0.1); 
        }
        #calendar { 
            background: white; 
            padding: 20px; 
            border-radius: 12px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.05); 
        }
        .section-title { font-weight: 600; color: #333; margin-bottom: 25px; border-left: 5px solid #007bff; padding-left: 15px; }
        .btn-round { border-radius: 50px; padding: 10px 25px; }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>
    <div id="content">

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title mb-0">Venue Booking Dashboard</h2>
        <a href="my_venue_bookings.php" class="btn btn-outline-primary btn-round">My Bookings</a>
    </div>

    <?= $message; ?>

    <div class="card mb-5 border-0 shadow-sm" style="border-radius: 15px;">
        <div class="card-body">
            <form method="GET" class="row">
                <div class="col-md-9">
                    <input type="text" name="search" class="form-control form-control-lg border-0 bg-light" placeholder="Find a venue by name or location..." value="<?= htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary btn-lg btn-block btn-round">Search Venue</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <?php if($venues->num_rows > 0): ?>
            <?php while($row = $venues->fetch_assoc()): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card venue-card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <h5 class="card-title font-weight-bold"><?= htmlspecialchars($row['name']); ?></h5>
                                <span class="badge badge-pill badge-info py-2 px-3">Cap: <?= $row['capacity']; ?></span>
                            </div>
                            <p class="text-muted"><i class="small"><?= htmlspecialchars($row['location']); ?></i></p>
                            <p class="card-text text-secondary small"><?= mb_strimwidth(htmlspecialchars($row['description']), 0, 100, "..."); ?></p>
                            <button class="btn btn-primary btn-block mt-3" data-toggle="modal" data-target="#bookModal" 
                                    data-venue="<?= $row['id']; ?>" data-venue-name="<?= htmlspecialchars($row['name']); ?>">
                                Book This Venue
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <p class="text-muted">No venues found matching your criteria.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="mt-5">
        <h4 class="section-title">Availability Calendar</h4>
        <div id="calendar"></div>
    </div>
</div>

<div class="modal fade" id="bookModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form method="POST" class="modal-content border-0" style="border-radius: 15px;">
            <input type="hidden" name="book_venue" value="1">
            <input type="hidden" name="venue_id" id="modal-venue-id">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title">Book: <span id="modal-venue-name"></span></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body p-4">
                <div class="form-group">
                    <label class="font-weight-bold">Link to Your Event</label>
                    <select name="event_id" class="form-control custom-select" required>
                        <option value="">Choose an event...</option>
                        <?php $events->data_seek(0); while($e = $events->fetch_assoc()): ?>
                            <option value="<?= $e['event_id']; ?>"><?= htmlspecialchars($e['title']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Date</label>
                    <input type="date" name="booking_date" class="form-control" required min="<?= date('Y-m-d'); ?>">
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold">Start Time</label>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold">End Time</label>
                        <input type="time" name="end_time" class="form-control" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4">
                <button type="button" class="btn btn-light" data-dismiss="modal">Close</button>
                <button class="btn btn-primary px-4 btn-round">Confirm Booking</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

<script>
    $('#bookModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        $('#modal-venue-id').val(button.data('venue'));
        $('#modal-venue-name').text(button.data('venue-name'));
    });

    document.addEventListener('DOMContentLoaded', function() {
        var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
            initialView: 'dayGridMonth',
            themeSystem: 'bootstrap',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek'
            },
            events: <?= $json_events; ?>,
            eventTimeFormat: { hour: '2-digit', minute: '2-digit', meridiem: 'short' }
        });
        calendar.render();
    });
</script>
</body>
</html>