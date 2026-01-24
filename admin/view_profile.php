<?php
include('config.php');
session_start();

// Admin authentication
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// Get user ID from URL
$user_id = intval($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User not found.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>View User Profile</title>
    <meta charset="utf-8">
    <link rel="stylesheet"
          href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .profile-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #007bff;
        }
    </style>
</head>

<body>
<?php include('admin_navbar.php'); ?>

<div class="container mt-5">
    <div class="card mx-auto" style="max-width:600px;">
        <div class="card-body text-center">

            <?php if(!empty($user['profile_image'])): ?>
                <img src="../images/<?= htmlspecialchars($user['profile_image']); ?>" alt="Profile Image" class="profile-img mb-3">
            <?php else: ?>
                <img src="../images/default.png" alt="Profile Image" class="profile-img mb-3">
            <?php endif; ?>

            <h4 class="mb-3"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']); ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone'] ?? '-'); ?></p>
            <p><strong>Role:</strong> <?= ucfirst($user['role']); ?></p>
            <p><strong>Status:</strong> <?= ucfirst($user['status']); ?></p>
            <p><strong>Notifications:</strong> <?= $user['system_notifications'] ? 'Enabled' : 'Disabled'; ?></p>
            <p><strong>Created At:</strong> <?= $user['created_at']; ?></p>
            <p><strong>Updated At:</strong> <?= $user['updated_at']; ?></p>

            <a href="view_users.php" class="btn btn-secondary mt-3">Back to Users</a>
        </div>
    </div>
</div>

</body>
</html>
