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
    $mail->Password   = $_ENV['EMAIL_SMTP_PASSWORD'];   // Use your email Password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    // Sender and recipient
    $mail->setFrom($email, $name);              // From user
    $mail->addAddress($_ENV['EMAIL_SMTP_USER']);   // same email as on smtp settings above
    $mail->addReplyTo($email, $name);           // Reply goes back to sender

    // Email content
    $mail->isHTML(true);
    $mail->Subject = "Naisea Yona Website: Topic {$option}";
    $mail->Body = '
                <!DOCTYPE html>
                <html>

                <head>
                    <meta charset="UTF-8">
                    <title>New Contact Message</title>
                </head>

                <body style="font-family: Arial, sans-serif; background-color: #f8d9de; margin: 0; padding: 0;">
                    <table align="center" width="100%" cellpadding="0" cellspacing="0"
                        style="max-width: 600px; margin: 40px 40px; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                        <tr>
                            <td style="background-color: #e06263; color: white; text-align: center; padding: 20px;">
                                <h1 style="margin: 0; color: #121212">New Inquiry Received</h1>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 30px;">
                                <p style="font-size: 16px; color: #333;">Hello, Admin</p>
                                <p style="font-size: 15px; color: #333;">
                                    You&rsquo;ve received a new message from your website contact form.
                                </p>

                                <table width="100%" cellpadding="8" cellspacing="0"
                                    style="background-color: #f8d9de; border-radius: 6px; margin: 20px 0;">
                                    <tr>
                                        <td><strong>Name:</strong></td>
                                        <td>' . htmlspecialchars($name) . '</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email:</strong></td>
                                        <td>' . htmlspecialchars($email) . '</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Topic:</strong></td>
                                        <td>' . htmlspecialchars($_POST["option"]) . '</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Message:</strong></td>
                                        <td>' . nl2br(htmlspecialchars($_POST["message"])) . '</td>
                                    </tr>
                                </table>

                                <p style="font-size: 14px; color: #e06263;">Please respond to this message directly to reply to the
                                    sender.</p>

                                <hr style="border: none; border-top: 1px solid #e06263; margin: 30px 0;">

                                <p style="font-size: 12px; color: #888; text-align: center;">
                                    &copy; ' . date("Y") . ' CarryKindness. All rights reserved.
                                </p>
                            </td>
                        </tr>
                    </table>
                </body>

                </html>';

    $mail->AltBody = "New enquiry:\nName: {$name}\nEmail: {$email}\nTopic: {$option}\nMessage: {$message}";

    $mail->send();

    $response = ['success' => true, 'message' => 'Thank you! Your message has been sent successfully.'];
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Mailer Error: ' . $mail->ErrorInfo];
}

echo json_encode($response);
exit;
