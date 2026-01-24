<?php
include('config.php');
session_start();

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';

$message = "";

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $booking_id = intval($_POST['booking_id']);
    $new_status = $_POST['status'];

    // Update booking status
    $stmt = $conn->prepare("UPDATE venue_bookings SET status=? WHERE id=?");
    $stmt->bind_param("si", $new_status, $booking_id);
    if ($stmt->execute()) {

        // Fetch organizer email and event title
        $stmt2 = $conn->prepare("
            SELECT u.email, e.title 
            FROM venue_bookings vb 
            JOIN events e ON vb.event_id=e.event_id
            JOIN users u ON e.organizer_id=u.id
            WHERE vb.id=?
        ");
        $stmt2->bind_param("i", $booking_id);
        $stmt2->execute();
        $stmt2->bind_result($organizer_email, $event_title);
        $stmt2->fetch();
        $stmt2->close();

        // Send email using PHPMailer
        if (!empty($organizer_email)) {
            $mail = new PHPMailer(true);
            try {
                //Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'nasiryt.827@gmail.com'; // Your Gmail
                $mail->Password = 'ddgc zhft gohg bjmz';   // Your Gmail App password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                //Recipients
                $mail->setFrom('nasiryt.827@gmail.com', 'Admin Event Management');
                $mail->addAddress($organizer_email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = "Booking Status Updated";
                $mail->Body    = "<p>Your booking for <b>{$event_title}</b> has been updated to <b>{$new_status}</b>.</p>";

                $mail->send();
            } catch (Exception $e) {
                $message .= '<div class="alert alert-warning">Booking updated but email could not be sent.</div>';
            }
        }

        $message .= '<div class="alert alert-success">Booking status updated successfully.</div>';
    } else {
        $message .= '<div class="alert alert-danger">Failed to update booking status.</div>';
    }
    $stmt->close();
}

// Fetch all bookings with organizer info
$bookings = $conn->query("
    SELECT vb.id, e.title AS event_title, v.name AS venue_name, u.first_name, u.last_name, u.email AS organizer_email, 
           vb.booking_date, vb.start_time, vb.end_time, vb.status
    FROM venue_bookings vb
    JOIN events e ON vb.event_id=e.event_id
    JOIN venues v ON vb.venue_id=v.id
    JOIN users u ON e.organizer_id=u.id
    ORDER BY vb.booking_date DESC, vb.start_time ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>All Bookings | Admin Dashboard</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('admin_navbar.php'); ?>

<div class="container mt-5">
    <h3>All Venue Bookings</h3>
    <?php echo $message; ?>

    <table class="table table-bordered table-hover">
        <thead class="thead-dark">
            <tr>
                <th>#</th>
                <th>Event</th>
                <th>Venue</th>
                <th>Organizer</th>
                <th>Email</th>
                <th>Date</th>
                <th>Start</th>
                <th>End</th>
                <th>Status</th>
                <th>Update Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $bookings->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id']; ?></td>
                <td><?= htmlspecialchars($row['event_title']); ?></td>
                <td><?= htmlspecialchars($row['venue_name']); ?></td>
                <td><?= htmlspecialchars($row['first_name'].' '.$row['last_name']); ?></td>
                <td><?= htmlspecialchars($row['organizer_email']); ?></td>
                <td><?= $row['booking_date']; ?></td>
                <td><?= $row['start_time']; ?></td>
                <td><?= $row['end_time']; ?></td>
                <td><?= ucfirst($row['status']); ?></td>
                <td>
                    <form method="POST" class="form-inline">
                        <input type="hidden" name="booking_id" value="<?= $row['id']; ?>">
                        <input type="hidden" name="update_status" value="1">
                        <select name="status" class="form-control form-control-sm mr-2" required>
                            <option value="pending" <?= $row['status']=='pending'?'selected':''; ?>>Pending</option>
                            <option value="confirmed" <?= $row['status']=='confirmed'?'selected':''; ?>>Confirmed</option>
                            <option value="cancelled" <?= $row['status']=='cancelled'?'selected':''; ?>>Cancelled</option>
                        </select>
                        <button class="btn btn-dark mt-2 btn-sm">Update</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>
