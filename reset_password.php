<?php
include('config.php');

$email = $_GET['email'] ?? "";
$msg = "";
$show_form = false;

if (!empty($email)) {

    $stmt = $conn->prepare(
        "SELECT id FROM users WHERE email = ? AND status = 'active'"
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id);
        $stmt->fetch();
        $show_form = true;
    }

    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = $_POST['email'];
    $password = $_POST['password'];

    if (strlen($password) < 6) {
        $msg = "Password must be at least 6 characters";
    } else {

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $update = $conn->prepare(
            "UPDATE users SET password_hash = ? WHERE email = ?"
        );
        $update->bind_param("ss", $hash, $email);
        $update->execute();

        $msg = "Password reset successful. You may login now.";
        $show_form = false;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container mt-5">
    <div class="card mx-auto" style="max-width:450px;">
        <div class="card-body">

            <h4 class="text-center mb-4">Reset Password</h4>

            <?php if ($msg): ?>
                <div class="alert alert-info text-center"><?php echo $msg; ?></div>
            <?php endif; ?>

            <?php if ($show_form): ?>
                <form method="post">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="text-center">
                        <button class="btn btn-primary px-4">Reset Password</button>
                    </div>
                </form>
            <?php endif; ?>

        </div>
    </div>
</div>

</body>
</html>
