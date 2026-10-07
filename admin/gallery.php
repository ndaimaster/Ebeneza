<?php
require_once __DIR__ . '/_layout.php';
require_admin();
require_once __DIR__ . '/../includes/upload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify($_POST['csrf'] ?? null)) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $path = save_upload('image', __DIR__ . '/../uploads/gallery', ['jpg','jpeg','png','webp'], 5 * 1024 * 1024);
        if ($path === null) { $error = 'Valid image required (jpg/png/webp, max 5MB).'; }
        else {
            $stmt = db()->prepare('INSERT INTO gallery (title, description, image_path, category, activity_date, photo_consent, published) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute([trim($_POST['title']), trim($_POST['description'] ?? '') ?: null, $path, trim($_POST['category'] ?? '') ?: null, $_POST['activity_date'] ?: null, isset($_POST['photo_consent']) ? 1 : 0, isset($_POST['published']) ? 1 : 0]);
            audit_log(current_admin()['id'] ?? null, 'add_gallery', 'gallery', (int)db()->lastInsertId());
            header('Location: ' . url('admin/gallery.php')); exit;
        }
    }
    if ($action === 'toggle' && ctype_digit($_POST['id'] ?? '')) {
        db()->prepare('UPDATE gallery SET published = 1 - published WHERE id=?')->execute([(int)$_POST['id']]);
        audit_log(current_admin()['id'] ?? null, 'toggle_gallery', 'gallery', (int)$_POST['id']);
        header('Location: ' . url('admin/gallery.php')); exit;
    }
    if ($action === 'delete' && ctype_digit($_POST['id'] ?? '')) {
        $stmt = db()->prepare('SELECT image_path FROM gallery WHERE id=?'); $stmt->execute([(int)$_POST['id']]);
        if ($p = $stmt->fetchColumn()) @unlink(dirname(__DIR__) . '/' . $p);
        db()->prepare('DELETE FROM gallery WHERE id=?')->execute([(int)$_POST['id']]);
        header('Location: ' . url('admin/gallery.php')); exit;
    }
}

$rows = db()->query('SELECT * FROM gallery ORDER BY activity_date DESC, created_at DESC')->fetchAll();
admin_head('Gallery');
?>
<h1 class="text-2xl font-bold text-[#0F5B66] mb-4">Gallery</h1>
<?php if (!empty($error)): ?><p class="text-red-600 mb-3"><?= h($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="bg-white rounded-2xl shadow p-5 mb-6 grid sm:grid-cols-2 gap-3 text-sm">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="add">
  <input name="title" required placeholder="Activity title" class="border rounded-xl px-3 py-2">
  <input name="category" placeholder="Category (food distribution, education...)" class="border rounded-xl px-3 py-2">
  <input name="activity_date" type="date" class="border rounded-xl px-3 py-2">
  <input name="description" placeholder="Description" class="border rounded-xl px-3 py-2">
  <input type="file" name="image" accept="image/*" required class="border rounded-xl px-3 py-2 sm:col-span-2">
  <label class="flex items-center gap-2"><input type="checkbox" name="photo_consent"> Written photo consent on file</label>
  <label class="flex items-center gap-2"><input type="checkbox" name="published"> Publish</label>
  <button class="bg-[#0F5B66] text-white px-5 py-2 rounded-xl font-semibold sm:col-span-2">Add photo</button>
</form>
<div class="grid sm:grid-cols-3 gap-4">
<?php foreach ($rows as $r): ?>
  <div class="bg-white rounded-2xl shadow p-3 text-sm">
    <img src="<?= h(url($r['image_path'])) ?>" class="rounded-xl w-full h-40 object-cover" alt="<?= h($r['title']) ?>">
    <p class="font-semibold mt-2"><?= h($r['title']) ?></p>
    <p class="text-gray-500 text-xs"><?= h($r['category']) ?> · <?= h($r['activity_date']) ?> · <?= $r['published'] ? 'Published' : 'Draft' ?></p>
        <form method="post" class="mt-2 flex gap-3"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="text-amber-600"><?= $r['published'] ? 'Unpublish' : 'Publish' ?></button></form>
    <form method="post" onsubmit="return confirm('Delete?')" class="mt-2"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="text-red-600">Delete</button></form>
  </div>
<?php endforeach; ?>
</div>
<?php admin_foot();
