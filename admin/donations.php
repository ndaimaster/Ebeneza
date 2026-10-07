<?php
require_once __DIR__ . '/_layout.php';
require_admin();

$where = [];
$params = [];
if (!empty($_GET['status']) && in_array($_GET['status'], ['pending','under_review','confirmed','rejected'], true)) { $where[] = 'status = ?'; $params[] = $_GET['status']; }
if (!empty($_GET['q'])) {
    $where[] = '(reference LIKE ? OR name LIKE ? OR email LIKE ?)';
    $like = '%' . $_GET['q'] . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
}
$sql = 'SELECT id, reference, name, email, phone, amount, status, created_at FROM donations';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY created_at DESC LIMIT 200';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

admin_head('Donations');
?>
<?php if (!empty($_GET['msg'])): ?><p class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 mb-4 text-sm"><?= h($_GET['msg']) ?></p><?php endif; ?>
<h1 class="text-2xl font-bold text-[#0F5B66] mb-4">Donations</h1>
<form class="bg-white rounded-2xl shadow p-4 mb-4 flex flex-wrap gap-3 text-sm" method="get">
  <input name="q" placeholder="Search reference, name or email" value="<?= h($_GET['q'] ?? '') ?>" class="border rounded-xl px-3 py-2 flex-1 min-w-[220px]">
  <select name="status" class="border rounded-xl px-3 py-2"><option value="">All statuses</option>
    <?php foreach (['pending','under_review','confirmed','rejected'] as $s): ?>
    <option value="<?= $s ?>" <?= ($_GET['status'] ?? '')===$s?'selected':'' ?>><?= h($s) ?></option><?php endforeach; ?>
  </select>
  <button class="bg-[#0F5B66] text-white px-5 py-2 rounded-xl font-semibold">Filter</button>
</form>
<div class="bg-white rounded-2xl shadow p-4 overflow-x-auto">
<table class="w-full text-sm"><thead><tr class="text-left text-gray-500"><th>Reference</th><th>Donor</th><th>Email</th><th>Amount</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr class="border-t">
  <td><a class="text-[#0F5B66] font-semibold" href="<?= url('admin/donation.php?id=') ?><?= (int)$r['id'] ?>"><?= h($r['reference']) ?></a></td>
  <td><?= h($r['name']) ?></td>
  <td><?= h($r['email']) ?></td>
  <td><?= h('TZS ' . number_format((float)$r['amount'], 2)) ?></td>
  <td class="font-semibold"><?= h($r['status']) ?></td>
  <td><?= h($r['created_at']) ?></td>
  <td>
    <div class="flex flex-wrap items-center gap-2">
      <a class="text-[#0F5B66] underline" href="<?= url('admin/donation.php?id=') ?><?= (int)$r['id'] ?>">View</a>
      <form method="post" action="<?= url('admin/donation.php?id=') ?><?= (int)$r['id'] ?>" class="inline flex items-center gap-1">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="set_status">
        <select name="new_status" class="border rounded-xl px-2 py-1 text-xs">
          <option value="pending" <?= $r['status']==='pending'?'selected':'' ?>>Pending</option>
          <option value="under_review" <?= $r['status']==='under_review'?'selected':'' ?>>Under Review</option>
          <option value="confirmed" <?= $r['status']==='confirmed'?'selected':'' ?>>Confirmed</option>
          <option value="rejected" <?= $r['status']==='rejected'?'selected':'' ?>>Rejected</option>
        </select>
        <button class="bg-[#0F5B66] text-white px-2 py-1 rounded-lg text-xs">Change</button>
      </form>
      <form method="post" action="<?= url('admin/donation.php?id=') ?><?= (int)$r['id'] ?>" onsubmit='return confirm(<?= h(json_encode("Are you sure you want to permanently delete this donation?\n\nDonor: " . $r['name'] . "\nReference: " . $r['reference'] . "\nAmount: TZS " . number_format((float)$r['amount'], 2) . "\nStatus: " . $r['status'])) ?>)'>
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="delete">
        <button class="bg-red-700 text-white px-2 py-1 rounded-lg text-xs">Delete</button>
      </form>
    </div>
  </td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
<?php admin_foot();
