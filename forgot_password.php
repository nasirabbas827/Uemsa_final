<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';
require 'phpmailer/src/Exception.php';

include('config.php');

$msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email']);

    $stmt = $conn->prepare(
        "SELECT first_name FROM users WHERE email = ? AND status = 'active'"
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {

        $stmt->bind_result($first_name);
        $stmt->fetch();

        $reset_link = "http://localhost/fall25/uems/reset_password.php?email=" . urlencode($email);

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
            $mail->Subject = 'Reset Password - UEMS';
            $mail->Body = "
                <p>Hello <strong>$first_name</strong>,</p>
                <p>Click below to reset your password:</p>
                <p>
                    <a href='$reset_link'
                       style='padding:10px 15px;background:#007bff;color:#fff;text-decoration:none;'>
                       Reset Password
                    </a>
                </p>
            ";

            $mail->send();
        } catch (Exception $e) {}

        $msg = "Password reset link sent to your email.";

    } else {
        $msg = "No active account found with this email.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container mt-5">
    <div class="card mx-auto" style="max-width:450px;">
        <div class="card-body">

            <h4 class="text-center mb-4">Forgot Password</h4>

            <?php if ($msg): ?>
                <div class="alert alert-info text-center"><?php echo $msg; ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="text-center">
                    <button class="btn btn-primary px-4">Send Reset Link</button>
                </div>
            </form>

        </div>
    </div>
</div>

</body>
</html>
