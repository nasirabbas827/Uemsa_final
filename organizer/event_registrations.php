<?php
session_start();
include('config.php');

// Auth check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Event Organizer') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$organizer_id = $_SESSION['user_id'];

/* ===============================
   FETCH REGISTRATIONS
================================ */
$sql = "
SELECT 
    er.id                AS registration_id,
    er.status            AS registration_status,
    er.cancellation_reason,
    er.registered_at,

    u.first_name,
    u.last_name,
    u.email,

    e.title              AS event_title,
    e.event_id
FROM event_registrations er
INNER JOIN events e ON er.event_id = e.event_id
INNER JOIN users u  ON er.user_id = u.id
WHERE e.organizer_id = ?
ORDER BY er.registered_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $organizer_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Event Registrations | Organizer</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>
    <div id="content">

<div class="container mt-5">
    <h3 class="mb-4">Registrations for My Events</h3>

    <table class="table table-bordered table-hover">
        <thead class="thead-dark">
        <tr>
            <th>Event</th>
            <th>Student</th>
            <th>Email</th>
            <th>Status</th>
            <th>Registered On</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['event_title']); ?></td>
                    <td><?= htmlspecialchars($row['first_name'].' '.$row['last_name']); ?></td>
                    <td><?= htmlspecialchars($row['email']); ?></td>
                    <td>
                        <span class="badge badge-info">
                            <?= ucfirst($row['registration_status']); ?>
                        </span>
                    </td>
                    <td><?= date("d M Y", strtotime($row['registered_at'])); ?></td>
                    <td>
                        <button class="btn btn-sm btn-primary"
                                data-toggle="modal"
                                data-target="#regModal<?= $row['registration_id']; ?>">
                            View / Update
                        </button>
                    </td>
                </tr>

                <!-- ================= MODAL ================= -->
                <div class="modal fade" id="regModal<?= $row['registration_id']; ?>">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">

                            <form method="POST" action="update_registration.php">

                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        Registration Details – <?= htmlspecialchars($row['event_title']); ?>
                                    </h5>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>

                                <div class="modal-body">

                                    <input type="hidden" name="registration_id"
                                           value="<?= $row['registration_id']; ?>">

                                    <!-- STATUS -->
                                    <div class="form-group">
                                        <label>Registration Status</label>
                                        <select name="status" class="form-control">
                                            <?php
                                            $statuses = [
                                                'pending',
                                                'confirmed',
                                                'waitlisted',
                                                'cancelled',
                                                'attended',
                                                'no_show'
                                            ];
                                            foreach ($statuses as $status):
                                            ?>
                                                <option value="<?= $status; ?>"
                                                    <?= $row['registration_status'] === $status ? 'selected' : ''; ?>>
                                                    <?= ucfirst($status); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- CANCELLATION REASON -->
                                    <div class="form-group">
                                        <label>Cancellation Reason (Organizer Only)</label>
                                        <textarea name="cancellation_reason"
                                                  class="form-control"
                                                  rows="3"><?= htmlspecialchars($row['cancellation_reason']); ?></textarea>
                                    </div>

                                    <hr>

                                    <!-- REGISTRATION RESPONSES -->
                                    <h5 class="mb-3">Submitted Form Responses</h5>

                                    <?php
                                    $respSql = "
                                    SELECT rf.field_label, rr.response_value
                                    FROM registration_responses rr
                                    INNER JOIN registration_forms rf 
                                        ON rr.field_id = rf.id
                                    WHERE rr.registration_id = ?
                                    ";
                                    $respStmt = $conn->prepare($respSql);
                                    $respStmt->bind_param("i", $row['registration_id']);
                                    $respStmt->execute();
                                    $responses = $respStmt->get_result();
                                    ?>

                                    <?php if ($responses->num_rows > 0): ?>
                                        <?php while ($resp = $responses->fetch_assoc()): ?>
                                            <p>
                                                <strong><?= htmlspecialchars($resp['field_label']); ?>:</strong>
                                                <?= htmlspecialchars($resp['response_value']); ?>
                                            </p>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <p class="text-muted">No responses submitted.</p>
                                    <?php endif; ?>

                                </div>

                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-success">
                                        Update Registration
                                    </button>
                                    <button type="button" class="btn btn-secondary"
                                            data-dismiss="modal">
                                        Close
                                    </button>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>
                <!-- =============== END MODAL =============== -->

            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" class="text-center">No registrations found.</td>
            </tr>
        <?php endif; ?>

        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
