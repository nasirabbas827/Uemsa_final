<?php
include('config.php');

if (isset($_GET['email'])) {

    $email = $_GET['email'];

    $stmt = $conn->prepare("
        UPDATE users 
        SET status = 'active' 
        WHERE email = ? AND status = 'pending'
    ");

    $stmt->bind_param("s", $email);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "Your account has been activated successfully. You can now login.";
    } else {
        echo "Invalid or already activated account.";
    }

    $stmt->close();
}

$conn->close();
?>
