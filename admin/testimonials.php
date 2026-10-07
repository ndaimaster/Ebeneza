<?php
require_once __DIR__ . '/_layout.php';
require_admin();
require_once __DIR__ . '/../includes/upload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify($_POST['csrf'] ?? null)) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $img = null;
        if (!empty($_FILES['image']['name'])) $img = save_upload('image', __DIR__ . '/../uploads/gallery', ['jpg','jpeg','png','webp'], 3 * 1024 * 1024);
        $stmt = db()->prepare('INSERT INTO testimonials (title, beneficiary_label, location, situation, intervention, outcome, case_date, image_path, photo_consent, published) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([trim($_POST['title']), trim($_POST['beneficiary_label'] ?? '') ?: null, trim($_POST['location'] ?? '') ?: null, trim($_POST['situation'] ?? '') ?: null, trim($_POST['intervention'] ?? '') ?: null, trim($_POST['outcome'] ?? '') ?: null, $_POST['case_date'] ?: null, $img, isset($_POST['photo_consent']) ? 1 : 0, isset($_POST['published']) ? 1 : 0]);
        header('Location: ' . url('admin/testimonials.php')); exit;
    }
    if ($action === 'toggle' && ctype_digit($_POST['id'] ?? '')) {
        db()->prepare('UPDATE testimonials SET published = 1 - published WHERE id=?')->execute([(int)$_POST['id']]);
        header('Location: ' . url('admin/testimonials.php')); exit;
    }
    if ($action === 'delete' && ctype_digit($_POST['id'] ?? '')) {
        db()->prepare('DELETE FROM testimonials WHERE id=?')->execute([(int)$_POST['id']]);
        header('Location: ' . url('admin/testimonials.php')); exit;
    }
}
$rows = db()->query('SELECT * FROM testimonials ORDER BY case_date DESC, created_at DESC')->fetchAll();
admin_head('Testimonials / Case Studies');
?>
<h1 class="text-2xl font-bold text-[#0F5B66] mb-4">Testimonials &amp; Case Studies</h1>
<p class="text-sm text-gray-500 mb-4">Never publish full names, phone numbers or sensitive details of children. Only publish cases where consent is recorded.</p>
<form method="post" enctype="multipart/form-data" class="bg-white rounded-2xl shadow p-5 mb-6 grid gap-3 text-sm">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="add">
  <div class="grid sm:grid-cols-2 gap-3">
    <input name="title" required placeholder="Case study title" class="border rounded-xl px-3 py-2">
    <input name="beneficiary_label" placeholder="Anonymous label (e.g. 'A 14-year-old girl')" class="border rounded-xl px-3 py-2">
    <input name="location" placeholder="Location" class="border rounded-xl px-3 py-2">
    <input name="case_date" type="date" class="border rounded-xl px-3 py-2">
  </div>
  <textarea name="situation" placeholder="Situation" class="border rounded-xl px-3 py-2"></textarea>
  <textarea name="intervention" placeholder="Intervention" class="border rounded-xl px-3 py-2"></textarea>
  <textarea name="outcome" placeholder="Outcome" class="border rounded-xl px-3 py-2"></textarea>
  <input type="file" name="image" accept="image/*" class="border rounded-xl px-3 py-2">
  <label class="flex items-center gap-2"><input type="checkbox" name="photo_consent"> Consent documented</label>
  <label class="flex items-center gap-2"><input type="checkbox" name="published"> Publish</label>
  <button class="bg-[#0F5B66] text-white px-5 py-2 rounded-xl font-semibold">Add case study</button>
</form>
<div class="bg-white rounded-2xl shadow p-5">
<?php foreach ($rows as $r): ?>
<div class="border-b py-2 text-sm flex justify-between"><span><strong><?= h($r['title']) ?></strong> — <?= h($r['location']) ?> (<?= $r['published']?'Published':'Draft' ?>)</span>
<form method="post"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="text-amber-600 mr-3"><?= $r['published'] ? 'Unpublish' : 'Publish' ?></button></form>
<form method="post" onsubmit="return confirm('Delete?')"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="text-red-600">Delete</button></form></div>
<?php endforeach; ?>
</div>
<?php admin_foot();
