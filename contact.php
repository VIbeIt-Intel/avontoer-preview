<?php
/**
 * Quote form handler for Xneelo (PHP mail).
 * Upload with the static site export from the `out/` folder.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw ?: '', true);
if (!is_array($input)) {
    $input = $_POST;
}

if (!empty($input['_honeypot'])) {
    echo json_encode(['ok' => true]);
    exit;
}

$name = trim((string) ($input['name'] ?? ''));
$email = trim((string) ($input['email'] ?? ''));
$message = trim((string) ($input['message'] ?? ''));

if ($name === '' || $email === '' || $message === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Please fill in all required fields.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid email address.']);
    exit;
}

$to = 'info@avontoer.co.za';
$subject = 'New quote request from ' . $name;
$body = implode("\n", [
    'Name: ' . $name,
    'Email: ' . $email,
    'Phone: ' . (($input['phone'] ?? '') !== '' ? $input['phone'] : '—'),
    'Trip type: ' . (($input['tripType'] ?? '') !== '' ? $input['tripType'] : '—'),
    'Destination / dates: ' . (($input['destination'] ?? '') !== '' ? $input['destination'] : '—'),
    '',
    $message,
]);

$host = $_SERVER['HTTP_HOST'] ?? 'avontoer.co.za';
$from = 'noreply@' . preg_replace('/[^a-z0-9.-]/i', '', $host);
$headers = "From: Avontoer Website <{$from}>\r\n";
$headers .= 'Reply-To: ' . $email . "\r\n";

$sent = @mail($to, $subject, $body, $headers);

if ($sent) {
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(500);
echo json_encode([
    'ok' => false,
    'error' => 'Could not send your message. Please email info@avontoer.co.za or call +27763593717.',
]);
