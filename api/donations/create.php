<?php
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../services/DonationService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'Method not allowed'], 405);
if (!rate_limit('donation_create', 10, 600)) json_response(['error' => 'Too many requests. Please try later.'], 429);

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$name = trim((string)($input['name'] ?? ''));
$email = trim((string)($input['email'] ?? ''));
$phone = trim((string)($input['phone'] ?? ''));
$amount = $input['amount'] ?? '';

$errors = [];
if ($name === '') $errors[] = 'Full name is required.';
if (mb_strlen($name) > 150) $errors[] = 'Name is too long.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) $errors[] = 'A valid email address is required.';
if ($phone !== '' && !preg_match('/^[+0-9][0-9\s\-()]{6,29}$/', $phone)) $errors[] = 'Invalid phone number.';
if (!is_numeric($amount)) $errors[] = 'Amount must be numeric.';
elseif ((float)$amount <= 0) $errors[] = 'Amount must be greater than zero.';
elseif ((float)$amount > 500000000) $errors[] = 'Amount is too large.';

if ($errors) json_response(['error' => implode(' ', $errors)], 422);

try {
    $created = DonationService::create($name, $email, $phone ?: null, (float)$amount);
} catch (Throwable $e) {
    error_log($e->getMessage());
    json_response(['error' => 'Could not register the donation.'], 500);
}

// Optional: send a "request registered" email. Never blocks the donation.
@send_email($email, 'Your donation request was registered',
    '<p>Dear ' . h($name) . ',</p>' .
    '<p>Your donation request has been registered. This email only confirms that we received your request — it is not a payment confirmation.</p>' .
    '<p>Reference: <strong>' . h($created['reference']) . '</strong><br>Amount: TZS ' . number_format((float)$amount, 2) . '</p>' .
    '<p>Ebeneza Foundation, P.O. Box 12597, Arusha, Tanzania</p>');

json_response([
    'reference' => $created['reference'],
    'status' => 'pending',
    'message' => 'Donation request registered. Please complete the payment using the instructions provided.',
]);
