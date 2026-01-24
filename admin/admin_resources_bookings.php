<?php
session_start();
include('config.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';

// Admin authentication
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}
$message = "";

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = intval($_POST['id']);
    $new_status = $_POST['status'];

    // Update the allocation
    $stmt = $conn->prepare("UPDATE event_resources SET status=? WHERE id=?");
    $stmt->bind_param("si", $new_status, $id);
    if ($stmt->execute()) {

        // Fetch organizer email
        $stmt2 = $conn->prepare("
            SELECT u.email, e.title 
            FROM event_resources er
            JOIN events e ON er.event_id=e.event_id
            JOIN users u ON e.organizer_id=u.id
            WHERE er.id=?
        ");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $stmt2->bind_result($email, $event_title);
        $stmt2->fetch();
        $stmt2->close();

        // Send email notification
        if (!empty($email)) {
            $mail = new PHPMailer(true);
            try {
$mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'nasiryt.827@gmail.com'; // Your Gmail
                $mail->Password = 'ddgc zhft gohg bjmz';   // Your Gmail App password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('nasiryt.827@gmail.com', 'Event Admin');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = "Resource Allocation Status Updated";
                $mail->Body    = "Hello,<br>Your resource allocation for event <b>{$event_title}</b> has been updated to <b>{$new_status}</b>.<br>Regards,<br>Admin Team";

                $mail->send();
            } catch (Exception $e) {
                $message .= "<div class='alert alert-warning'>Email could not be sent: {$mail->ErrorInfo}</div>";
            }
        }

        $message .= '<div class="alert alert-success">Status updated successfully.</div>';
    } else {
        $message .= '<div class="alert alert-danger">Failed to update status.</div>';
    }
    $stmt->close();
}

// Fetch all resource allocations
$allocations = $conn->query("
    SELECT er.id, e.title AS event_title, r.resource_name, er.quantity_allocated, r.total_quantity, er.status, u.first_name, u.last_name, u.email
    FROM event_resources er
    JOIN events e ON er.event_id=e.event_id
    JOIN resources r ON er.resource_id=r.id
    JOIN users u ON e.organizer_id=u.id
    ORDER BY er.id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Resource Allocations | Admin</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include('admin_navbar.php'); ?>

<div class="container mt-5">
    <h3>Resource Allocations</h3>
    <?php echo $message; ?>

    <table class="table table-bordered table-hover">
        <thead class="thead-dark">
            <tr>
                <th>#</th>
                <th>Event</th>
                <th>Organizer</th>
                <th>Resource</th>
                <th>Quantity Allocated</th>
                <th>Total Available</th>
                <th>Status</th>
                <th>Update Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $allocations->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id']; ?></td>
                <td><?= htmlspecialchars($row['event_title']); ?></td>
                <td><?= htmlspecialchars($row['first_name'].' '.$row['last_name']); ?> (<?= $row['email']; ?>)</td>
                <td><?= htmlspecialchars($row['resource_name']); ?></td>
                <td><?= $row['quantity_allocated']; ?></td>
                <td><?= $row['total_quantity']; ?></td>
                <td><?= ucfirst($row['status']); ?></td>
                <td>
                    <form method="POST" class="form-inline">
                        <input type="hidden" name="id" value="<?= $row['id']; ?>">
                        <select name="status" class="form-control form-control-sm mr-1">
                            <option value="pending" <?= $row['status']=='pending'?'selected':''; ?>>Pending</option>
                            <option value="confirmed" <?= $row['status']=='confirmed'?'selected':''; ?>>Confirmed</option>
                            <option value="cancelled" <?= $row['status']=='cancelled'?'selected':''; ?>>Cancelled</option>
                        </select>
                        <button type="submit" name="update_status" class="mt-2 btn btn-primary btn-sm">Update</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
