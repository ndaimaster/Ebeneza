<?php
// CLI: create/update the first (or additional) admin account.
// Usage: php scripts/create-admin.php --email=admin@example.org --name="Admin Name" [--password=secret]
require_once __DIR__ . '/../config/database.php';

if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only\n"); exit(1); }

$opts = getopt('', ['email:', 'name:', 'password::']);
$email = $opts['email'] ?? null;
$name = $opts['name'] ?? null;

if (!$email || !$name) {
    fwrite(STDERR, "Usage: php scripts/create-admin.php --email=... --name=... [--password=...]\n");
    exit(1);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { fwrite(STDERR, "Invalid email\n"); exit(1); }

$pass = $opts['password'] ?? null;
if ($pass === null) {
    fwrite(STDOUT, "Enter password: ");
    $pass = trim(fgets(STDIN));
}
if (strlen($pass) < 12) { fwrite(STDERR, "Password must be at least 12 characters.\n"); exit(1); }

$hash = password_hash($pass, PASSWORD_DEFAULT);
$stmt = db()->prepare('SELECT id FROM admins WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetchColumn()) {
    db()->prepare('UPDATE admins SET password_hash=?, name=? WHERE email=?')->execute([$hash, $name, $email]);
    fwrite(STDOUT, "Admin updated.\n");
} else {
    db()->prepare('INSERT INTO admins (name, email, password_hash) VALUES (?,?,?)')->execute([$name, $email, $hash]);
    fwrite(STDOUT, "Admin created.\n");
}
