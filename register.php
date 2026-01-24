<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';


include('config.php');

$first_name = $last_name = $email = $phone = $role = "";
$errors = [];
$success_msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // First Name
    if (empty(trim($_POST['first_name']))) {
        $errors[] = "First name is required";
    } else {
        $first_name = trim($_POST['first_name']);
    }

    // Last Name
    if (empty(trim($_POST['last_name']))) {
        $errors[] = "Last name is required";
    } else {
        $last_name = trim($_POST['last_name']);
    }

    // Role
    $allowed_roles = ['Student','Faculty','Event Organizer'];
    if (!isset($_POST['role']) || !in_array($_POST['role'], $allowed_roles)) {
        $errors[] = "Invalid role selected";
    } else {
        $role = $_POST['role'];
    }

    // Email
    if (empty(trim($_POST['email']))) {
        $errors[] = "Email is required";
    } else {
        $email = trim($_POST['email']);
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Email already registered";
        }
        $stmt->close();
    }

    // Password
    if (empty($_POST['password']) || strlen($_POST['password']) < 6) {
        $errors[] = "Password must be at least 6 characters";
    } else {
        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
    }

    // Phone (Optional)
    $phone = !empty($_POST['phone']) ? trim($_POST['phone']) : NULL;

    if (empty($errors)) {

        $stmt = $conn->prepare("
            INSERT INTO users 
            (role, first_name, last_name, email, password_hash, phone, status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");

        $stmt->bind_param(
            "ssssss",
            $role,
            $first_name,
            $last_name,
            $email,
            $password_hash,
            $phone
        );

        if ($stmt->execute()) {

            // Verification email (email-based, no token)
            $verify_link = "http://localhost/fall25/uems/verify_email.php?email=" . urlencode($email);

            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'nasiryt.827@gmail.com'; // Your Gmail
                $mail->Password = 'ddgc zhft gohg bjmz';   // Your Gmail App password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('nasiryt.827@gmail.com', 'UEMS');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = 'Verify Your Account - UEMS';
                $mail->Body = "
                    <p>Hello <strong>$first_name</strong>,</p>
                    <p>Please click the button below to activate your account.</p>
                    <p>
                        <a href='$verify_link'
                           style='padding:10px 15px;background:#007bff;color:#fff;text-decoration:none;'>
                           Verify Account
                        </a>
                    </p>
                ";

                $mail->send();

            } catch (Exception $e) {
                // Email failure should not block registration
            }

            $success_msg = "Registration successful. Please check your email to activate your account.";

            // Reset form values
            $first_name = $last_name = $email = $phone = $role = "";

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

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container mt-5">
    <div class="card shadow mx-auto" style="max-width: 800px;">
        <div class="card-body">

            <h3 class="text-center mb-4">User Registration</h3>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success text-center">
                    <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>

            <form method="post" novalidate>

                <!-- Row 1 -->
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" class="form-control"
                               value="<?php echo htmlspecialchars($first_name); ?>">
                    </div>

                    <div class="col-md-6 form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" class="form-control"
                               value="<?php echo htmlspecialchars($last_name); ?>">
                    </div>
                </div>

                <!-- Row 2 -->
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?php echo htmlspecialchars($email); ?>">
                    </div>

                    <div class="col-md-6 form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control">
                    </div>
                </div>

                <!-- Row 3 -->
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Phone (Optional)</label>
                        <input type="text" name="phone" class="form-control"
                               value="<?php echo htmlspecialchars($phone); ?>">
                    </div>

                    <div class="col-md-6 form-group">
                        <label>Register As</label>
                        <select name="role" class="form-control">
                            <option value="">Select Role</option>
                            <option value="Student" <?php if ($role === 'Student') echo 'selected'; ?>>Student</option>
                            <option value="Faculty" <?php if ($role === 'Faculty') echo 'selected'; ?>>Faculty</option>
                            <option value="Event Organizer" <?php if ($role === 'Event Organizer') echo 'selected'; ?>>Event Organizer</option>
                        </select>
                    </div>
                </div>

                <!-- Button -->
                <div class="row">
                    <div class="col text-center mt-4">
                        <button type="submit" class="btn btn-primary px-5">Register</button>
                    </div>
                </div>

            </form>

        </div>
    </div>
</div>

</body>
</html>
