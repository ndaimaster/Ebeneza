<?php
require_once __DIR__ . '/_layout.php';
require_admin();
require_once __DIR__ . '/../includes/upload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify($_POST['csrf'] ?? null)) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $path = save_upload('file', __DIR__ . '/../uploads/documents', ['pdf','png','jpg','jpeg','webp','doc','docx'], 10 * 1024 * 1024);
        if ($path === null) { $error = 'Invalid or missing file (max 10MB).'; }
        else {
            $stmt = db()->prepare('INSERT INTO documents (title, document_type, description, file_path, year, published) VALUES (?,?,?,?,?,?)');
            $stmt->execute([trim($_POST['title']), trim($_POST['document_type']), trim($_POST['description'] ?? '') ?: null, $path, $_POST['year'] !== '' ? (int)$_POST['year'] : null, isset($_POST['published']) ? 1 : 0]);
            audit_log(current_admin()['id'] ?? null, 'upload_document', 'documents', (int)db()->lastInsertId());
            header('Location: ' . url('admin/documents.php')); exit;
        }
    }
    if ($action === 'delete' && ctype_digit($_POST['id'] ?? '')) {
        $old = (int)$_POST['id'];
        $stmt = db()->prepare('SELECT file_path FROM documents WHERE id=?'); $stmt->execute([$old]);
        if ($p = $stmt->fetchColumn()) @unlink(__DIR__ . '/../' . $p);
        db()->prepare('DELETE FROM documents WHERE id=?')->execute([$old]);
        audit_log(current_admin()['id'] ?? null, 'delete_document', 'documents', $old);
        header('Location: ' . url('admin/documents.php')); exit;
    }
    if ($action === 'toggle' && ctype_digit($_POST['id'] ?? '')) {
        db()->prepare('UPDATE documents SET published = 1 - published WHERE id=?')->execute([(int)$_POST['id']]);
        header('Location: ' . url('admin/documents.php')); exit;
    }
}

$rows = db()->query('SELECT * FROM documents ORDER BY year DESC, created_at DESC')->fetchAll();
admin_head('Documents');
?>
<h1 class="text-2xl font-bold text-[#0F5B66] mb-4">Documents</h1>
<?php if (!empty($error)): ?><p class="text-red-600 mb-3"><?= h($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="bg-white rounded-2xl shadow p-5 mb-6 grid sm:grid-cols-2 gap-3 text-sm">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="add">
  <input name="title" required placeholder="Document title" class="border rounded-xl px-3 py-2">
  <input name="document_type" required placeholder="Type (Annual Report, Registration...)" class="border rounded-xl px-3 py-2">
  <input name="year" type="number" placeholder="Year" class="border rounded-xl px-3 py-2">
  <input name="description" placeholder="Description (optional)" class="border rounded-xl px-3 py-2">
  <input type="file" name="file" required class="border rounded-xl px-3 py-2 sm:col-span-2">
  <label class="flex items-center gap-2"><input type="checkbox" name="published"> Publish on website</label>
  <button class="bg-[#0F5B66] text-white px-5 py-2 rounded-xl font-semibold sm:col-span-2">Upload</button>
</form>
<div class="bg-white rounded-2xl shadow p-5 overflow-x-auto">
<table class="w-full text-sm"><thead><tr class="text-left text-gray-500"><th>Title</th><th>Type</th><th>Year</th><th>Published</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr class="border-t">
  <td><a class="text-[#0F5B66] underline" href="<?= url($r['file_path']) ?>" target="_blank"><?= h($r['title']) ?></a></td>
  <td><?= h($r['document_type']) ?></td><td><?= h($r['year']) ?></td><td><?= $r['published']?'Yes':'No' ?></td>
  <td class="flex gap-3">
    <form method="post"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="text-amber-600">Toggle</button></form>
    <form method="post" onsubmit="return confirm('Delete?')"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="text-red-600">Delete</button></form>
  </td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
<?php admin_foot();
