<?php
require_once __DIR__ . '/_layout.php';
$admin = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify($_POST['csrf'] ?? null)) {
    foreach ($_POST['settings'] ?? [] as $key => $value) {
        if (!preg_match('/^[a-z_]+$/', $key)) continue;
        $stmt = db()->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
        $stmt->execute([$key, trim((string)$value)]);
    }
    audit_log((int)$admin['id'], 'update_settings');
    header('Location: ' . url('admin/settings.php?saved=1')); exit;
}

$rows = db()->query('SELECT setting_key, setting_value FROM site_settings ORDER BY setting_key')->fetchAll(PDO::FETCH_KEY_PAIR);
admin_head('Settings');
?>
<h1 class="text-2xl font-bold text-[#0F5B66] mb-4">Site Settings</h1>
<?php if (isset($_GET['saved'])): ?><p class="text-green-600 mb-3">Settings saved.</p><?php endif; ?>
<form method="post" class="bg-white rounded-2xl shadow p-5 grid sm:grid-cols-2 gap-3 text-sm">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
  <?php foreach ($rows as $key => $value): ?>
  <label class="block"><span class="text-gray-500 text-xs"><?= h($key) ?></span>
    <input name="settings[<?= h($key) ?>]" value="<?= h($value) ?>" class="w-full border rounded-xl px-3 py-2">
  </label>
  <?php endforeach; ?>
  <button class="bg-[#0F5B66] text-white px-5 py-2 rounded-xl font-semibold sm:col-span-2">Save settings</button>
</form>
<?php admin_foot();
