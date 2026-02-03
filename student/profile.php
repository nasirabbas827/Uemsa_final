<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

/* ================= FETCH USER ================= */
$stmt = $conn->prepare("
    SELECT first_name, last_name, email, phone, department, profile_image, system_notifications
    FROM users WHERE id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result(
    $first_name,
    $last_name,
    $email,
    $phone,
    $department,
    $profile_image,
    $system_notifications
);
$stmt->fetch();
$stmt->close();

$departments = [
    'Computer Science',
    'Software Engineering',
    'Information Technology',
    'Business Administration',
    'Electrical Engineering',
    'Other'
];

/* ================= UPDATE PROFILE ================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $phone      = trim($_POST['phone']);
    $department = $_POST['department'] ?? '';
    $system_notifications = isset($_POST['system_notifications']) ? 1 : 0;

    $errors = [];

    if ($first_name === '' || $last_name === '') {
        $errors[] = "First and last name are required";
    }

    if (!in_array($department, $departments)) {
        $errors[] = "Invalid department selected";
    }

    // Password handling
    $password_sql = "";
    $password_param = null;

    if (!empty($_POST['password'])) {
        if (strlen($_POST['password']) < 8) {
            $errors[] = "Password must be at least 8 characters";
        } elseif ($_POST['password'] !== ($_POST['confirm_password'] ?? '')) {
            $errors[] = "Passwords do not match";
        } else {
            $password_param = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $password_sql = ", password_hash=?";
        }
    }

    // Profile image upload
    $image_sql = "";
    $image_param = null;

    if (!empty($_FILES['profile_image']['name'])) {
        $allowed = ['jpg','jpeg','png'];
        $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $errors[] = "Only JPG, JPEG, PNG images allowed";
        } else {
            $file_name = uniqid() . "." . $ext;
            $target_path = "../images/" . $file_name;

            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_path)) {
                $image_param = $file_name;
                $image_sql = ", profile_image=?";
            } else {
                $errors[] = "Profile image upload failed";
            }
        }
    }

    if (empty($errors)) {

        $sql = "
            UPDATE users SET
                first_name=?,
                last_name=?,
                phone=?,
                department=?,
                system_notifications=?
                $password_sql
                $image_sql
            WHERE id=?
        ";

        $stmt = $conn->prepare($sql);

        $params = [
            $first_name,
            $last_name,
            $phone,
            $department,
            $system_notifications
        ];

        $types = "ssssi";

        if ($password_param !== null) {
            $params[] = $password_param;
            $types .= "s";
        }

        if ($image_param !== null) {
            $params[] = $image_param;
            $types .= "s";
        }

        $params[] = $user_id;
        $types .= "i";

        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Profile updated successfully</div>';
            header("Refresh:1");
        } else {
            $message = '<div class="alert alert-danger">Update failed</div>';
        }

        $stmt->close();
    } else {
        $message = '<div class="alert alert-danger"><ul><li>' .
                   implode('</li><li>', $errors) .
                   '</li></ul></div>';
    }
}

$conn->close();
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
            width:120px;height:120px;object-fit:cover;
            border-radius:50%;border:3px solid #007bff;
        }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container mt-5">
    <div class="card mx-auto" style="max-width:750px;">
        <div class="card-body text-center">

            <img src="../images/<?= $profile_image ? htmlspecialchars($profile_image) : 'default.png'; ?>"
                 class="profile-img mb-3">

            <h4 class="mb-3">My Profile</h4>
            <?= $message ?>

            <form method="POST" enctype="multipart/form-data" class="text-left">

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>First Name</label>
                        <input type="text" name="first_name" class="form-control"
                               value="<?= htmlspecialchars($first_name) ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Last Name</label>
                        <input type="text" name="last_name" class="form-control"
                               value="<?= htmlspecialchars($last_name) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email (Read Only)</label>
                    <input type="email" class="form-control" value="<?= htmlspecialchars($email) ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Department</label>
                    <select name="department" class="form-control" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d ?>" <?= $department === $d ? 'selected' : '' ?>><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control"
                               value="<?= htmlspecialchars($phone) ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label>New Password (Optional)</label>
                        <input type="password" name="password" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control">
                </div>

                <div class="form-group">
                    <label>Profile Image</label>
                    <input type="file" name="profile_image" class="form-control-file">
                </div>

                <div class="form-check mb-3">
                    <input type="checkbox" name="system_notifications" class="form-check-input"
                           <?= $system_notifications ? 'checked' : '' ?>>
                    <label class="form-check-label">Enable System Notifications</label>
                </div>

                <div class="text-center">
                    <button class="btn btn-primary px-4">Update Profile</button>
                </div>

            </form>

        </div>
    </div>
</div>

</body>
</html>
