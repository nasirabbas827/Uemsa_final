<?php
session_start();
include('../config.php');

// Authentication Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Event Organizer') {
    header("Location: ../login.php");
    exit;
}

// Handle CRUD operations
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $event_id = intval($_POST['event_id']);
        $file_type = $_POST['file_type'] ?? '';

        // Upload file if adding or editing
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "../images/";
            $file_name = basename($_FILES["file"]["name"]);
            $target_file = $target_dir . $file_name;
            move_uploaded_file($_FILES["file"]["tmp_name"], $target_file);
        }

        if ($action === 'add' && $event_id && !empty($file_name) && !empty($file_type)) {
            $stmt = $conn->prepare("INSERT INTO event_media (event_id, file, file_type) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $event_id, $file_name, $file_type);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'edit' && isset($_POST['id'])) {
            $id = intval($_POST['id']);
            if (!empty($file_name)) {
                $stmt = $conn->prepare("UPDATE event_media SET event_id=?, file=?, file_type=? WHERE id=?");
                $stmt->bind_param("issi", $event_id, $file_name, $file_type, $id);
            } else {
                $stmt = $conn->prepare("UPDATE event_media SET event_id=?, file_type=? WHERE id=?");
                $stmt->bind_param("isi", $event_id, $file_type, $id);
            }
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'delete' && isset($_POST['id'])) {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM event_media WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }

        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
}

// Fetch media with event title
$result = $conn->query("
    SELECT em.id, em.event_id, em.file, em.file_type, e.title AS event_title
    FROM event_media em
    LEFT JOIN events e ON em.event_id = e.event_id
    ORDER BY em.id DESC
");

// Fetch events for dropdown
$events_result = $conn->query("SELECT event_id, title FROM events ORDER BY title ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Media | Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>
    <div id="content">

<div class="container mt-5">
    <div>
        <h3>Event Media</h3>
        <button class="btn btn-success" data-toggle="modal" data-target="#addModal">Add Media</button>
    </div>

    <table class="table table-bordered table-hover mt-3">
        <thead class="thead-dark">
            <tr>
                <th>#</th>
                <th>Event</th>
                <th>File</th>
                <th>Type</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id']; ?></td>
                <td><?= htmlspecialchars($row['event_title']); ?></td>
                <td>
                    <?php if($row['file_type'] === 'image'): ?>
                        <img src="../images/<?= htmlspecialchars($row['file']); ?>" alt="" style="width:80px;">
                    <?php else: ?>
                        <a href="../images/<?= htmlspecialchars($row['file']); ?>" target="_blank"><?= htmlspecialchars($row['file']); ?></a>
                    <?php endif; ?>
                </td>
                <td><?= ucfirst($row['file_type']); ?></td>
                <td>
                    <button class="btn btn-primary btn-sm" 
                            data-toggle="modal" 
                            data-target="#editModal" 
                            data-id="<?= $row['id']; ?>" 
                            data-event="<?= $row['event_id']; ?>" 
                            data-type="<?= $row['file_type']; ?>">Edit</button>

                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Delete this media?');">
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
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Add Event Media</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div class="form-group">
                <label>Event</label>
                <select name="event_id" class="form-control" required>
                    <option value="">Select Event</option>
                    <?php while($event = $events_result->fetch_assoc()): ?>
                        <option value="<?= $event['event_id']; ?>"><?= htmlspecialchars($event['title']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>File</label>
                <input type="file" name="file" class="form-control-file" required>
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="file_type" class="form-control" required>
                    <option value="image">Image</option>
                    <option value="pdf">PDF</option>
                </select>
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
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="edit-id">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Edit Event Media</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div class="form-group">
                <label>Event</label>
                <select name="event_id" id="edit-event" class="form-control" required>
                    <option value="">Select Event</option>
                    <?php
                    $events_result2 = $conn->query("SELECT event_id, title FROM events ORDER BY title ASC");
                    while($event = $events_result2->fetch_assoc()):
                    ?>
                        <option value="<?= $event['event_id']; ?>"><?= htmlspecialchars($event['title']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>File (Optional)</label>
                <input type="file" name="file" class="form-control-file">
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="file_type" id="edit-type" class="form-control" required>
                    <option value="image">Image</option>
                    <option value="pdf">PDF</option>
                </select>
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
    $('#editModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        $('#edit-id').val(button.data('id'));
        $('#edit-event').val(button.data('event'));
        $('#edit-type').val(button.data('type'));
    });
</script>

</body>
</html>
