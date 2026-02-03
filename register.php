<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';

include('config.php');

$first_name = $last_name = $email = $phone = $role = $department = "";
$errors = [];
$success_msg = "";

$allowed_roles = ['Student','Faculty','Event Organizer'];
$allowed_departments = [
    'Computer Science',
    'Software Engineering',
    'Information Technology',
    'Business Administration',
    'Electrical Engineering',
    'Other'
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // First Name
    $first_name = trim($_POST['first_name'] ?? '');
    if ($first_name === '') {
        $errors[] = "First name is required";
    }

    // Last Name
    $last_name = trim($_POST['last_name'] ?? '');
    if ($last_name === '') {
        $errors[] = "Last name is required";
    }

    // Role
    $role = $_POST['role'] ?? '';
    if (!in_array($role, $allowed_roles)) {
        $errors[] = "Invalid role selected";
    }

    // Department
    $department = $_POST['department'] ?? '';
    if (!in_array($department, $allowed_departments)) {
        $errors[] = "Invalid department selected";
    }

    // Email (University only)
    $email = trim($_POST['email'] ?? '');
    if ($email === '') {
        $errors[] = "Email is required";
    } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@vu\.edu\.pk$/', $email)) {
        $errors[] = "Email must be a valid university email (@vu.edu)";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Email already registered";
        }
        $stmt->close();
    }

    // Password + Confirm Password
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
    }

    // Phone (Optional)
    $phone = !empty($_POST['phone']) ? trim($_POST['phone']) : NULL;

    // Insert if no errors
    if (empty($errors)) {

        $stmt = $conn->prepare("
            INSERT INTO users 
            (role, department, first_name, last_name, email, password_hash, phone, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");

        $stmt->bind_param(
            "sssssss",
            $role,
            $department,
            $first_name,
            $last_name,
            $email,
            $password_hash,
            $phone
        );

        if ($stmt->execute()) {

            $verify_link = "http://localhost/fall25/uems/verify_email.php?email=" . urlencode($email);

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'nasiryt.827@gmail.com';
                $mail->Password = 'ddgc zhft gohg bjmz';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('nasiryt.827@gmail.com', 'UEMS');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = 'Verify Your Account - UEMS';
                $mail->Body = "
                    <p>Hello <strong>$first_name</strong>,</p>
                    <p>Please verify your university account:</p>
                    <p>
                        <a href='$verify_link'
                           style='padding:10px 15px;background:#007bff;color:#fff;text-decoration:none;'>
                           Verify Account
                        </a>
                    </p>
                ";
                $mail->send();
            } catch (Exception $e) {}

            $success_msg = "Registration successful. Please check your university email.";
            $first_name = $last_name = $email = $phone = $role = $department = "";
        } else {
            $errors[] = "Registration failed";
        }

        $stmt->close();
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Registration - UEMS</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container mt-5">
    <div class="card shadow mx-auto" style="max-width: 850px;">
        <div class="card-body">

            <h3 class="text-center mb-4">User Registration</h3>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success_msg): ?>
                <div class="alert alert-success text-center">
                    <?= htmlspecialchars($success_msg) ?>
                </div>
            <?php endif; ?>

            <form method="post">

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($first_name) ?>" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($last_name) ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>University Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Phone (Optional)</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($phone) ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Register As</label>
                        <select name="role" class="form-control" required>
                            <option value="">Select Role</option>
                            <?php foreach ($allowed_roles as $r): ?>
                                <option value="<?= $r ?>" <?= $role === $r ? 'selected' : '' ?>><?= $r ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6 form-group">
                        <label>Department</label>
                        <select name="department" class="form-control" required>
                            <option value="">Select Department</option>
                            <?php foreach ($allowed_departments as $d): ?>
                                <option value="<?= $d ?>" <?= $department === $d ? 'selected' : '' ?>><?= $d ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary px-5">Register</button>
                </div>

            </form>

        </div>
    </div>
</div>

</body>
</html>
