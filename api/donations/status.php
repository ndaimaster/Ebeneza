<?php
require_once __DIR__ . '/../../includes/helpers.php';

$ref = trim($_GET['reference'] ?? '');
if ($ref === '' || !preg_match('/^EBZ-\d{4}-[A-Z0-9]{6}$/', $ref)) {
    json_response(['error' => 'Invalid reference.'], 400);
}

$stmt = db()->prepare('SELECT reference, amount, status, created_at FROM donations WHERE reference = ?');
$stmt->execute([$ref]);
$row = $stmt->fetch();
if (!$row) json_response(['error' => 'No donation was found for this reference.'], 404);

// Only safe, public information is returned here.
json_response([
    'reference' => $row['reference'],
    'amount' => (float)$row['amount'],
    'currency' => 'TZS',
    'date' => $row['created_at'],
    'status' => $row['status'],
]);
