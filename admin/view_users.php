<?php
include('config.php');
session_start();

// Admin authentication
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: view_users.php");
    exit;
}

// Fetch all users
$result = mysqli_query(
    $conn,
    "SELECT id, CONCAT(first_name,' ',last_name) AS name, email, phone, role, status 
     FROM users ORDER BY id DESC"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Users</title>
    <meta charset="utf-8">
    <link rel="stylesheet"
          href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>

<?php include('admin_navbar.php'); ?>

<div class="container mt-5">

    <div  >
        <h3>All Users</h3>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Status</th>
                <th width="250">Actions</th>
            </tr>
            </thead>

            <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['phone'] ?? '-') ?></td>
                    <td><?= ucfirst(str_replace('_',' ', $row['role'])) ?></td>
                    <td><?= ucfirst($row['status']) ?></td>

                    <td>
                        <a href="view_profile.php?id=<?= $row['id'] ?>"
                           class="btn btn-info btn-sm">View</a>

                         
                        <a href="?delete=<?= $row['id'] ?>"
                           onclick="return confirm('Are you sure you want to delete this user?');"
                           class="btn btn-danger btn-sm">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>

        </table>
    </div>

</div>

</body>
</html>
