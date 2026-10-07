<?php
require_once __DIR__ . '/_layout.php';
require_admin();
$docsForSource = db()->query("SELECT id, title, year FROM documents ORDER BY year DESC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify($_POST['csrf'] ?? null)) {
    if (($_POST['action'] ?? '') === 'add') {
        $stmt = db()->prepare('INSERT INTO impact_statistics (metric_name, value, unit, year, description, source_title, source_document_id, verified, published) VALUES (?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            trim($_POST['metric_name']), trim($_POST['value']), trim($_POST['unit'] ?? '') ?: null,
            (int)$_POST['year'], trim($_POST['description'] ?? '') ?: null, trim($_POST['source_title'] ?? '') ?: null,
            ($_POST['source_document_id'] ?? '') !== '' ? (int)$_POST['source_document_id'] : null,
            isset($_POST['verified']) ? 1 : 0, isset($_POST['published']) ? 1 : 0,
        ]);
        audit_log(current_admin()['id'] ?? null, 'add_impact_statistic', 'impact_statistics', (int)db()->lastInsertId());
    }
    if (($_POST['action'] ?? '') === 'delete' && ctype_digit($_POST['id'] ?? '')) {
        $old = (int)$_POST['id'];
        db()->prepare('DELETE FROM impact_statistics WHERE id=?')->execute([$old]);
        audit_log(current_admin()['id'] ?? null, 'delete_impact_statistic', 'impact_statistics', $old);
    }
    if (($_POST['action'] ?? '') === 'toggle' && ctype_digit($_POST['id'] ?? '')) {
        db()->prepare('UPDATE impact_statistics SET published = 1 - published WHERE id=?')->execute([(int)$_POST['id']]);
        audit_log(current_admin()['id'] ?? null, 'toggle_impact', 'impact_statistics', (int)$_POST['id']);
    }
    header('Location: ' . url('admin/impact.php')); exit;
}

$rows = db()->query('SELECT i.*, d.title AS doc_title FROM impact_statistics i LEFT JOIN documents d ON d.id = i.source_document_id ORDER BY i.year DESC, i.metric_name')->fetchAll();
admin_head('Impact Statistics');
?>
<h1 class="text-2xl font-bold text-[#0F5B66] mb-4">Impact Statistics</h1>
<p class="text-sm text-gray-500 mb-4">Only statistics with a verified source and "published" checked appear on the public site. Figures without verified sources are never presented as verified.</p>
<form method="post" class="bg-white rounded-2xl shadow p-5 mb-6 grid sm:grid-cols-2 gap-3 text-sm">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
  <input type="hidden" name="action" value="add">
  <input name="metric_name" required placeholder="Metric name (e.g. Children Reached)" class="border rounded-xl px-3 py-2">
  <input name="value" required placeholder="Value (e.g. 1,200+)" class="border rounded-xl px-3 py-2">
  <input name="unit" placeholder="Unit (optional)" class="border rounded-xl px-3 py-2">
  <input name="year" type="number" required placeholder="Year" class="border rounded-xl px-3 py-2">
  <input name="source_title" placeholder="Source title (e.g. 2026 Annual Impact Report)" class="border rounded-xl px-3 py-2">
  <select name="source_document_id" class="border rounded-xl px-3 py-2"><option value="">Linked source document (optional)</option>
    <?php foreach ($docsForSource as $d): ?><option value="<?= (int)$d['id'] ?>"><?= h($d['title'].' ('.($d['year'] ?? 'n/a').')') ?></option><?php endforeach; ?>
  </select>
  <input name="description" placeholder="Description (optional)" class="border rounded-xl px-3 py-2 sm:col-span-2">
  <label class="flex items-center gap-2"><input type="checkbox" name="verified"> Verified by Ebeneza Foundation</label>
  <label class="flex items-center gap-2"><input type="checkbox" name="published"> Published on website</label>
  <button class="bg-[#0F5B66] text-white px-5 py-2 rounded-xl font-semibold sm:col-span-2">Add statistic</button>
</form>
<div class="bg-white rounded-2xl shadow p-5 overflow-x-auto">
<table class="w-full text-sm"><thead><tr class="text-left text-gray-500"><th>Metric</th><th>Value</th><th>Year</th><th>Source</th><th>Verified</th><th>Published</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr class="border-t">
  <td><?= h($r['metric_name']) ?></td><td><?= h($r['value'].' '.($r['unit'] ?? '')) ?></td><td><?= (int)$r['year'] ?></td>
  <td><?= h($r['source_title'] ?: $r['doc_title']) ?></td>
  <td><?= $r['verified'] ? 'Yes' : 'No' ?></td><td><?= $r['published'] ? 'Yes' : 'No' ?></td>
  <td><form method="post" class="inline"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="text-amber-600 mr-3"><?= $r['published'] ? 'Unpublish' : 'Publish' ?></button></form>
<form method="post" onsubmit="return confirm('Delete?')"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="text-red-600">Delete</button></form></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
<?php admin_foot();
