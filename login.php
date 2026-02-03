<?php
include('config.php');
session_start();

$email = $password = $role = "";
$email_err = $password_err = $role_err = "";
$login_msg = "";

// Optional (UI only)
$remember_me = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Remember Me (UI only)
    $remember_me = isset($_POST['remember_me']);

    // Email
    if (empty($_POST['email'])) {
        $email_err = "Email is required";
    } else {
        $email = trim($_POST['email']);
    }

    // Password
    if (empty($_POST['password'])) {
        $password_err = "Password is required";
    } else {
        $password = trim($_POST['password']);
    }

    // Role
    if (empty($_POST['role'])) {
        $role_err = "Please select a role";
    } else {
        $role = trim($_POST['role']);
    }

    if (empty($email_err) && empty($password_err) && empty($role_err)) {

        $sql = "SELECT id, first_name, last_name, password_hash, role, status
                FROM users
                WHERE email = ? AND role = ?
                LIMIT 1";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $email, $role);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) === 1) {

            mysqli_stmt_bind_result(
                $stmt,
                $id,
                $first_name,
                $last_name,
                $hashed_password,
                $db_role,
                $status
            );
            mysqli_stmt_fetch($stmt);

            if ($status !== 'active') {

                $login_msg = '<div class="alert alert-warning text-center">
                                Your account is pending. Please verify your email to activate.
                              </div>';

            } elseif (!password_verify($password, $hashed_password)) {

                $password_err = "Invalid password";

            } else {

                $_SESSION['user_id']   = $id;
                $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                $_SESSION['role']      = $db_role;

                if ($db_role === 'Student') {
                    header("Location: student/dashboard.php");
                } elseif ($db_role === 'Faculty') {
                    header("Location: faculty/dashboard.php");
                } elseif ($db_role === 'Event Organizer') {
                    header("Location: organizer/dashboard.php");
                }
                exit;
            }

        } else {
            $email_err = "Account not found";
        }

        mysqli_stmt_close($stmt);
    }

    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Login - UEMS</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet"
          href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>

<?php include('navbar.php'); ?>

<div class="container mt-5">
    <div class="card shadow mx-auto" style="max-width: 500px;">
        <div class="card-body">

            <h4 class="text-center mb-4">User Login</h4>

            <?php echo $login_msg; ?>

            <form method="post" novalidate>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email"
                           name="email"
                           class="form-control <?php echo $email_err ? 'is-invalid' : ''; ?>"
                           value="<?php echo htmlspecialchars($email); ?>">
                    <div class="invalid-feedback"><?php echo $email_err; ?></div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password"
                           name="password"
                           class="form-control <?php echo $password_err ? 'is-invalid' : ''; ?>">
                    <div class="invalid-feedback"><?php echo $password_err; ?></div>
                </div>

                <div class="form-group">
                    <label>Login As</label>
                    <select name="role"
                            class="form-control <?php echo $role_err ? 'is-invalid' : ''; ?>">
                        <option value="">Select Role</option>
                        <option value="Student" <?php if ($role === 'Student') echo 'selected'; ?>>Student</option>
                        <option value="Faculty" <?php if ($role === 'Faculty') echo 'selected'; ?>>Faculty</option>
                        <option value="Event Organizer" <?php if ($role === 'Event Organizer') echo 'selected'; ?>>Event Organizer</option>
                    </select>
                    <div class="invalid-feedback"><?php echo $role_err; ?></div>
                </div>

                <!-- Remember Me -->
                <div class="form-group form-check">
                    <input type="checkbox"
                           class="form-check-input"
                           id="remember_me"
                           name="remember_me"
                           <?php if ($remember_me) echo 'checked'; ?>>
                    <label class="form-check-label" for="remember_me">
                        Remember Me
                    </label>
                </div>

                <div class="text-center mt-3">
                    <button type="submit" class="btn btn-primary px-5">Login</button>
                </div>

                <div class="text-center mt-3">
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>

            </form>

        </div>
    </div>
</div>

</body>
</html>
