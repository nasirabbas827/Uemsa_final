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

// Handle Status Update
if (isset($_POST['update_status'])) {
    $event_id = intval($_POST['event_id']);
    $new_status = $_POST['status'];

    // Update event status
    $stmt = $conn->prepare("UPDATE events SET status=?, updated_at=NOW() WHERE event_id=?");
    $stmt->bind_param("si", $new_status, $event_id);
    $stmt->execute();
    $stmt->close();

    // Get organizer email
    $stmt = $conn->prepare("SELECT u.email, u.first_name FROM users u 
                            JOIN events e ON e.organizer_id = u.id 
                            WHERE e.event_id=?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $stmt->bind_result($organizer_email, $organizer_name);
    $stmt->fetch();
    $stmt->close();

    // Send email to organizer
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
            $mail->setFrom('nasiryt.827@gmail.com', 'Admin');
            $mail->addAddress($organizer_email, $organizer_name);

            //Content
            $mail->isHTML(true);
            $mail->Subject = "Event Status Update";
            $mail->Body    = "Hello $organizer_name,<br>Your event status has been updated to: <b>$new_status</b>.<br>Thank you.";

            $mail->send();
        } catch (Exception $e) {
            // Optional: log error
        }
    }

    header("Location: admin_view_events.php");
    exit;
}

// Fetch all events with organizer and category
$sql = "SELECT e.*, u.first_name, u.last_name, c.name AS category_name 
        FROM events e
        JOIN users u ON e.organizer_id = u.id
        LEFT JOIN event_categories c ON e.category_id = c.id
        ORDER BY e.event_id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Events</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('admin_navbar.php'); ?>

<div class="container mt-5">

    <h3 class="mb-4">All Events</h3>

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Organizer</th>
                <th>Category</th>
                <th>Type</th>
                <th>Start</th>
                <th>End</th>
                <th>Status</th>
                <th width="180">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['event_id'] ?></td>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                    <td><?= htmlspecialchars($row['category_name'] ?? '-') ?></td>
                    <td><?= ucfirst(str_replace('_',' ', $row['event_type'])) ?></td>
                    <td><?= $row['start_datetime'] ?></td>
                    <td><?= $row['end_datetime'] ?></td>
                    <td><?= ucfirst($row['status']) ?></td>
                    <td>
                        <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#viewModal<?= $row['event_id'] ?>">View</button>
                        <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#statusModal<?= $row['event_id'] ?>">Update Status</button>
                    </td>
                </tr>

                <!-- View Modal -->
                <div class="modal fade" id="viewModal<?= $row['event_id'] ?>" tabindex="-1">
                  <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title"><?= htmlspecialchars($row['title']) ?></h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                      </div>
                      <div class="modal-body">
                        <?php if(!empty($row['banner_image'])): ?>
                            <img src="../images/<?= htmlspecialchars($row['banner_image']) ?>" class="img-fluid mb-3">
                        <?php endif; ?>
                        <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($row['description'])) ?></p>
                        <p><strong>Organizer:</strong> <?= htmlspecialchars($row['first_name'].' '.$row['last_name']) ?></p>
                        <p><strong>Category:</strong> <?= htmlspecialchars($row['category_name'] ?? '-') ?></p>
                        <p><strong>Type:</strong> <?= $row['event_type'] ?></p>
                        <p><strong>Start:</strong> <?= $row['start_datetime'] ?></p>
                        <p><strong>End:</strong> <?= $row['end_datetime'] ?></p>
                        <p><strong>Capacity:</strong> <?= $row['capacity'] ?: 'Unlimited' ?></p>
                        <p><strong>Registration Deadline:</strong> <?= $row['registration_deadline'] ?: '-' ?></p>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Status Update Modal -->
                <div class="modal fade" id="statusModal<?= $row['event_id'] ?>" tabindex="-1">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Update Status</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="event_id" value="<?= $row['event_id'] ?>">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control" required>
                                    <option value="pending" <?= $row['status']=='pending'?'selected':'' ?>>Pending</option>
                                    <option value="approved" <?= $row['status']=='approved'?'selected':'' ?>>Approved</option>
                                    <option value="rejected" <?= $row['status']=='rejected'?'selected':'' ?>>Rejected</option>
                                    <option value="cancelled" <?= $row['status']=='cancelled'?'selected':'' ?>>Cancelled</option>
                                    <option value="completed" <?= $row['status']=='completed'?'selected':'' ?>>Completed</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="update_status" class="btn btn-danger">Update</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
