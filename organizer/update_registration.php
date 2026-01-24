<?php
session_start();
include('config.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';

/* ===============================
   AUTH CHECK
================================ */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Event Organizer') {
    header("Location: ../login.php");
    exit;
}

 

/* ===============================
   INPUT
================================ */
$registration_id     = intval($_POST['registration_id']);
$status              = $_POST['status'];
$cancellation_reason = trim($_POST['cancellation_reason']);

/* ===============================
   UPDATE REGISTRATION
================================ */
$updateSql = "
UPDATE event_registrations
SET status = ?, cancellation_reason = ?
WHERE id = ?
";
$stmt = $conn->prepare($updateSql);
$stmt->bind_param("ssi", $status, $cancellation_reason, $registration_id);
$stmt->execute();
$stmt->close();

/* ===============================
   FETCH USER + EVENT DETAILS
================================ */
$infoSql = "
SELECT 
    u.email,
    u.first_name,
    u.last_name,
    e.title AS event_title
FROM event_registrations er
INNER JOIN users u  ON er.user_id = u.id
INNER JOIN events e ON er.event_id = e.event_id
WHERE er.id = ?
";
$stmt = $conn->prepare($infoSql);
$stmt->bind_param("i", $registration_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

/* ===============================
   SEND EMAIL
================================ */
$mail = new PHPMailer(true);

try {
    // SMTP CONFIG
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'nasiryt.827@gmail.com'; // Your Gmail
                $mail->Password = 'ddgc zhft gohg bjmz';   // Your Gmail App password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

    // EMAIL HEADERS
    $mail->setFrom('nasiryt.827@gmail.com', 'UEMS Notifications');
    $mail->addAddress($data['email'], $data['first_name'].' '.$data['last_name']);

    // CONTENT
    $mail->isHTML(true);
    $mail->Subject = 'Event Registration Status Updated';

    $reasonBlock = '';
    if (!empty($cancellation_reason)) {
        $reasonBlock = "
            <p><strong>Reason:</strong><br>
            {$cancellation_reason}</p>
        ";
    }

    $mail->Body = "
        <h3>Registration Status Update</h3>

        <p>Hello <strong>{$data['first_name']}</strong>,</p>

        <p>Your registration for the event
        <strong>{$data['event_title']}</strong> has been updated.</p>

        <p><strong>Current Status:</strong> ".ucfirst($status)."</p>

        {$reasonBlock}

        <p>If you have any questions, please contact the event organizer.</p>

        <br>
        <p>Regards,<br>
        <strong>UEMS Team</strong></p>
    ";

    $mail->send();

} catch (Exception $e) {
    // Optional: log error instead of showing
    error_log("Email Error: " . $mail->ErrorInfo);
}

/* ===============================
   REDIRECT BACK
================================ */
header("Location: event_registrations.php");
exit;
