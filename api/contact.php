<?php
require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'Method not allowed'], 405);
if (!rate_limit('contact', 5, 600)) json_response(['error' => 'Too many messages. Please try again later.'], 429);

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$message = trim($input['message'] ?? '');
$subject = trim($input['subject'] ?? '');

if ($name === '' || $message === '') json_response(['error' => 'Name and message are required.'], 422);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_response(['error' => 'Valid email is required.'], 422);
if (mb_strlen($message) > 5000) json_response(['error' => 'Message is too long.'], 422);

$stmt = db()->prepare('INSERT INTO contact_messages (name, email, phone, subject, message, ip_address) VALUES (?,?,?,?,?,?)');
$stmt->execute([$name, $email, trim($input['phone'] ?? '') ?: null, $subject ?: null, $message, $_SERVER['REMOTE_ADDR'] ?? null]);

json_response(['ok' => true, 'message' => 'Thank you. Your message has been received.']);
