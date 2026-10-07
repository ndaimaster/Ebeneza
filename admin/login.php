<?php
require_once __DIR__ . '/../includes/helpers.php';
start_session();

if (current_admin()) { header('Location: ' . url('admin/dashboard.php')); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf'] ?? null)) { $error = 'Invalid request.'; }
    elseif (!rate_limit('admin_login', 8, 900)) { $error = 'Too many attempts. Try again later.'; }
    else {
        $email = trim($_POST['email'] ?? '');
        $pass = (string)($_POST['password'] ?? '');
        $stmt = db()->prepare('SELECT * FROM admins WHERE email = ?');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        if ($admin && password_verify($pass, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$admin['id'];
            $_SESSION['admin_last_activity'] = time();
            db()->prepare('UPDATE admins SET last_login_at = NOW() WHERE id = ?')->execute([$admin['id']]);
            audit_log((int)$admin['id'], 'login');
            header('Location: ' . url('admin/dashboard.php'));
            exit;
        }
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login | Ebeneza Foundation</title>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script></head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
<form method="post" class="bg-white rounded-2xl shadow p-8 w-full max-w-sm space-y-4">
  <h1 class="text-2xl font-bold text-[#0F5B66]">Admin Login</h1>
  <a href="<?= url('index.html') ?>" class="text-sm text-gray-500 hover:text-[#0F5B66] underline">← Back to Home</a>
  <?php if ($error): ?><p class="text-red-600 text-sm"><?= h($error) ?></p><?php endif; ?>
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
  <div><label class="text-sm font-semibold">Email</label><input name="email" type="email" required class="w-full border rounded-xl px-4 py-2.5"></div>
  <div><label class="text-sm font-semibold">Password</label><input name="password" type="password" required class="w-full border rounded-xl px-4 py-2.5"></div>
  <button class="w-full bg-[#F4B942] font-bold py-3 rounded-xl">Sign in</button>
</form>
</body></html>
