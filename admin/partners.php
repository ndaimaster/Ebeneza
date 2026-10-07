<?php
require_once __DIR__ . '/_layout.php';
require_admin();
require_once __DIR__ . '/../includes/upload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify($_POST['csrf'] ?? null)) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $logo = null;
        if (!empty($_FILES['logo']['name'])) $logo = save_upload('logo', __DIR__ . '/../uploads/gallery', ['jpg','jpeg','png','webp'], 3 * 1024 * 1024);
        $stmt = db()->prepare('INSERT INTO partners (name, logo_path, description, website_url, published) VALUES (?,?,?,?,?)');
        $stmt->execute([trim($_POST['name']), $logo, trim($_POST['description'] ?? '') ?: null, trim($_POST['website_url'] ?? '') ?: null, isset($_POST['published']) ? 1 : 0]);
        header('Location: ' . url('admin/partners.php')); exit;
    }
    if ($action === 'toggle' && ctype_digit($_POST['id'] ?? '')) {
        db()->prepare('UPDATE partners SET published = 1 - published WHERE id=?')->execute([(int)$_POST['id']]);
        header('Location: ' . url('admin/partners.php')); exit;
    }
    if ($action === 'delete' && ctype_digit($_POST['id'] ?? '')) {
        db()->prepare('DELETE FROM partners WHERE id=?')->execute([(int)$_POST['id']]);
        header('Location: ' . url('admin/partners.php')); exit;
    }
}
$rows = db()->query('SELECT * FROM partners ORDER BY created_at DESC')->fetchAll();
admin_head('Partners');
?>
<h1 class="text-2xl font-bold text-[#0F5B66] mb-4">Partners</h1>
<p class="text-sm text-gray-500 mb-4">Only add partners Ebeneza Foundation can verify. The public page shows placeholder text when no partners are published.</p>
<form method="post" enctype="multipart/form-data" class="bg-white rounded-2xl shadow p-5 mb-6 grid sm:grid-cols-2 gap-3 text-sm">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="add">
  <input name="name" required placeholder="Partner name" class="border rounded-xl px-3 py-2">
  <input name="website_url" placeholder="Website URL" class="border rounded-xl px-3 py-2">
  <input name="description" placeholder="Description" class="border rounded-xl px-3 py-2 sm:col-span-2">
  <input type="file" name="logo" accept="image/*" class="border rounded-xl px-3 py-2">
  <label class="flex items-center gap-2"><input type="checkbox" name="published"> Publish</label>
  <button class="bg-[#0F5B66] text-white px-5 py-2 rounded-xl font-semibold sm:col-span-2">Add partner</button>
</form>
<div class="bg-white rounded-2xl shadow p-5">
<?php foreach ($rows as $r): ?>
<div class="border-b py-2 text-sm flex justify-between"><span><strong><?= h($r['name']) ?></strong> — <?= h($r['website_url']) ?> (<?= $r['published']?'Published':'Draft' ?>)</span>
<form method="post"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="text-amber-600 mr-3"><?= $r['published'] ? 'Unpublish' : 'Publish' ?></button></form>
<form method="post" onsubmit="return confirm('Delete?')"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="text-red-600">Delete</button></form></div>
<?php endforeach; ?>
</div>
<?php admin_foot();
