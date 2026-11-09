
<?php

session_start();

function redirectContact(string $status): never
{
    header('Location: contact.php?status=' . urlencode($status));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.php');
    exit;
}

if (!empty($_POST['website'])) {
    redirectContact('success');
}

$csrfToken = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['contact_csrf'] ?? '';

if (
    !is_string($csrfToken) ||
    !is_string($sessionToken) ||
    $sessionToken === '' ||
    !hash_equals($sessionToken, $csrfToken)
) {
    redirectContact('invalid');
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

$allowedSubjects = [
    'General inquiry',
    'Services information',
    'Appointment inquiry',
    'Prices and offers',
    'Other'
];

if (
    $name === '' ||
    mb_strlen($name) > 150 ||
    $email === '' ||
    strlen($email) > 150 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    mb_strlen($phone) > 30 ||
    !in_array($subject, $allowedSubjects, true) ||
    $message === '' ||
    mb_strlen($message) > 3000
) {
    redirectContact('invalid');
}

try {
    require_once __DIR__ . '/../config/database.php';

    $sql = "INSERT INTO contact_messages
            (name, email, phone, subject, message)
            VALUES
            (:name, :email, :phone, :subject, :message)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':phone' => $phone !== '' ? $phone : null,
        ':subject' => $subject,
        ':message' => $message
    ]);

    $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));

    redirectContact('success');

} catch (PDOException $e) {
    error_log('Contact message error: ' . $e->getMessage());
    redirectContact('error');
}
