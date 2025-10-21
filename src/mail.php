<?php
//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php'; // Correct path to autoloader

// ✅ Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();



header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Unknown error occurred'];

// Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Validate and sanitize input
$name    = isset($_POST['name']) ? trim($_POST['name']) : null;
$email   = isset($_POST['email']) ? trim($_POST['email']) : null;
$option  = isset($_POST['option']) ? trim($_POST['option']) : null;
$message = isset($_POST['message']) ? trim($_POST['message']) : null;

// Basic validation
if (!$name || !$email || !$option || !$message) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}

// Configure PHPMailer
$mail = new PHPMailer(true);

try {
    // Gmail SMTP settings
    $mail->isSMTP();
    $mail->Host       = $_ENV['EMAIL_SMTP_HOST'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['EMAIL_SMTP_USER']; // Replace with your email
    $mail->Password   = $_ENV['EMAIL_SMTP_PASSSWORD'];   // Use your email Password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    // Sender and recipient
    $mail->setFrom($email, $name);              // From user
    $mail->addAddress($_ENV['EMAIL_SMTP_USER']);   // same email as on smtp settings above
    $mail->addReplyTo($email, $name);           // Reply goes back to sender

    // Email content
    $mail->isHTML(true);
    $mail->Subject = "New enquiry: {$option}";
    //todo update email ui to match website when possible
    $mail->Body = "
        <h2>New enquiry received</h2>
        <p><strong>Name:</strong> {$name}</p>
        <p><strong>Email:</strong> {$email}</p>
        <p><strong>Topic:</strong> {$option}</p>
        <p><strong>Message:</strong><br>{$message}</p>
        <hr>
        <p>Sent from your website contact form.</p>
    ";

    $mail->AltBody = "New enquiry:\nName: {$name}\nEmail: {$email}\nTopic: {$option}\nMessage: {$message}";

    $mail->send();

    $response = ['success' => true, 'message' => 'Thank you! Your message has been sent successfully.'];
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Mailer Error: ' . $mail->ErrorInfo];
}

echo json_encode($response);
exit;