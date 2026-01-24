<?php
session_start();
include('config.php');

// Authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];
$message = "";

/* ================= FETCH USER ================= */
$sql = "SELECT first_name, last_name, email, phone, profile_image, system_notifications 
        FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $first_name, $last_name, $email, $phone, $profile_image, $system_notifications);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

/* ================= UPDATE PROFILE ================= */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $phone      = trim($_POST['phone']);
    $system_notifications = isset($_POST['system_notifications']) ? 1 : 0;

    $password_sql = "";
    $profile_sql  = "";
    $params = [$first_name, $last_name, $phone, $system_notifications, $user_id];

    // Handle password if entered
    if (!empty($_POST['password'])) {
        $hashed_password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
        $password_sql = ", password_hash=?";
        array_splice($params, -2, 0, $hashed_password); // insert before system_notifications
    }

    // Handle profile image upload
    if (!empty($_FILES['profile_image']['name'])) {
        $target_dir = "../images/";
        $file_name = basename($_FILES["profile_image"]["name"]);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $profile_sql = ", profile_image=?";
            array_splice($params, -2, 0, $file_name); // insert before system_notifications
        } else {
            $message = '<div class="alert alert-warning">Profile image upload failed.</div>';
        }
    }

    $update_sql = "UPDATE users 
                   SET first_name=?, last_name=?, phone=? $password_sql $profile_sql, system_notifications=?
                   WHERE id=?";

    $stmt = mysqli_prepare($conn, $update_sql);

    // Dynamically bind types
    $types = "ssiii"; // default types for first_name,last_name,phone,system_notifications,user_id
    if (!empty($_POST['password']) && !empty($_FILES['profile_image']['name'])) {
        $types = "ssssiis"; // Actually password and profile_image both strings
        $types = "ssssii";  // string,string,string,string,int,int (password+image both string)
    } elseif (!empty($_POST['password'])) {
        $types = "ssssii"; // first,last,phone,password,system_notifications,user_id
    } elseif (!empty($_FILES['profile_image']['name'])) {
        $types = "ssssii"; // first,last,phone,profile_image,system_notifications,user_id
    }

    mysqli_stmt_bind_param($stmt, $types, ...$params);

    if (mysqli_stmt_execute($stmt)) {
        $message = '<div class="alert alert-success">Profile updated successfully</div>';
        header("Refresh:1");
    } else {
        $message = '<div class="alert alert-danger">Update failed</div>';
    }

    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Profile</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .profile-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #007bff;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>
    <div id="content">

<div class="container mt-5">
    <div class="card mx-auto" style="max-width:700px;">
        <div class="card-body text-center">

            <?php if(!empty($profile_image)): ?>
                <img src="../images/<?= htmlspecialchars($profile_image); ?>" alt="Profile Image" class="profile-img">
            <?php else: ?>
                <img src="../images/default.png" alt="Profile Image" class="profile-img">
            <?php endif; ?>

            <h4 class="mb-4">My Profile</h4>
            <?php echo $message; ?>

            <form method="POST" enctype="multipart/form-data" class="text-left">

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>First Name</label>
                        <input type="text" name="first_name" value="<?= htmlspecialchars($first_name); ?>" class="form-control" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Last Name</label>
                        <input type="text" name="last_name" value="<?= htmlspecialchars($last_name); ?>" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email (Read Only)</label>
                    <input type="email" value="<?= htmlspecialchars($email); ?>" class="form-control" readonly>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($phone); ?>" class="form-control">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Password (Optional)</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter new password to change">
                    </div>
                </div>

                <div class="form-group">
                    <label>Profile Image (Optional)</label>
                    <input type="file" name="profile_image" class="form-control-file">
                </div>

                <div class="form-group form-check">
                    <input type="checkbox" name="system_notifications" class="form-check-input" id="system_notifications" <?= $system_notifications ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="system_notifications">Enable System Notifications</label>
                </div>

                <div class="text-center mt-3">
                    <button class="btn btn-primary px-4">Update Profile</button>
                </div>

            </form>

        </div>
    </div>
</div>

</body>
</html>
