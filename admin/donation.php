<?php
require_once __DIR__ . '/_layout.php';
$admin = require_admin();

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM donations WHERE id=?');
$stmt->execute([$id]);
$donation = $stmt->fetch();
if (!$donation) { http_response_code(404); echo 'Donation not found.'; exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify($_POST['csrf'] ?? null)) {
    $action = $_POST['action'] ?? '';
    $reason = trim($_POST['reason'] ?? '');

    $apply = function (string $newStatus, ?string $reasonText) use ($id, $donation, $admin) {
        db()->prepare('UPDATE donations SET status=?, updated_at=NOW() WHERE id=?')->execute([$newStatus, $id]);
        audit_log((int)$admin['id'], 'donation_' . $newStatus, 'donation', $id, $donation['status'], $newStatus . ($reasonText ? ' | ' . $reasonText : ''));
    };

    if ($action === 'set_status') {
        $new = trim($_POST['new_status'] ?? '');
        if (!in_array($new, ['pending','under_review','confirmed','rejected'], true)) {
            $error = 'Invalid status value.';
        } elseif ($new === $donation['status']) {
            $error = 'That is already the current status.';
        } else {
            $old = $donation['status'];
            db()->prepare('UPDATE donations SET status=?, updated_at=NOW() WHERE id=?')->execute([$new, $id]);
            audit_log((int)$admin['id'], 'donation_status_changed', 'donation', $id, $old, $new);
            header('Location: ' . url('admin/donations.php?msg=' . urlencode("Status of {$donation['reference']} changed from {$old} to {$new}."))); exit;
        }
    }
    if ($action === 'delete') {
        $oldSummary = json_encode([
            'reference' => $donation['reference'],
            'name' => $donation['name'],
            'amount' => $donation['amount'],
            'status' => $donation['status'],
        ], JSON_UNESCAPED_UNICODE);
        db()->prepare('DELETE FROM donations WHERE id=?')->execute([$id]);
        audit_log((int)$admin['id'], 'donation_deleted', 'donation', $id, $oldSummary, null);
        header('Location: ' . url('admin/donations.php?msg=' . urlencode("Donation {$donation['reference']} was deleted."))); exit;
    }
    if ($action === 'confirm') {
        $apply('confirmed', $reason !== '' ? $reason : null);
        header('Location: ' . url('admin/donation.php?id=' . $id)); exit;
    }
    if ($action === 'under_review') {
        $apply('under_review', $reason !== '' ? $reason : null);
        header('Location: ' . url('admin/donation.php?id=' . $id)); exit;
    }
    if ($action === 'reject') {
        if ($reason === '') {
            $error = 'A reason is required to reject a donation.';
        } else {
            $apply('rejected', $reason);
            header('Location: ' . url('admin/donation.php?id=' . $id)); exit;
        }
    }
}

$audits = db()->prepare('SELECT * FROM audit_logs WHERE entity_type=\'donation\' AND entity_id=? ORDER BY created_at DESC LIMIT 20');
$audits->execute([$id]);
$audits = $audits->fetchAll();

admin_head('Donation ' . $donation['reference']);
?>
<h1 class="text-2xl font-bold text-[#0F5B66] mb-4"><?= h($donation['reference']) ?></h1>
<?php if (!empty($error)): ?><p class="text-red-600 mb-3"><?= h($error) ?></p><?php endif; ?>
<div class="grid lg:grid-cols-2 gap-6">
  <div class="bg-white rounded-2xl shadow p-6 space-y-2 text-sm">
    <p><strong>Donor:</strong> <?= h($donation['name']) ?></p>
    <p><strong>Email:</strong> <?= h($donation['email']) ?></p>
    <p><strong>Phone:</strong> <?= h($donation['phone']) ?></p>
    <p><strong>Amount:</strong> <?= h('TZS ' . number_format((float)$donation['amount'], 2)) ?></p>
    <p><strong>Status:</strong> <span class="font-bold"><?= h($donation['status']) ?></span></p>
    <p><strong>Registered:</strong> <?= h($donation['created_at']) ?></p>
    <p><?php if ($donation['status'] === 'confirmed'): ?><a class="text-[#0F5B66] underline" href="<?= url('receipt.php?ref=') . urlencode($donation['reference']) ?>" target="_blank">Receipt</a><?php endif; ?></p>
  </div>
  <div class="bg-white rounded-2xl shadow p-6">
    <h2 class="font-bold mb-3">Review action</h2>
    <form method="post" class="space-y-3 text-sm" onsubmit="return confirm('Change the donation status?')">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <textarea name="reason" placeholder="Reason (required for rejection, optional otherwise)" class="w-full border rounded-xl p-3"></textarea>
      <div class="flex gap-2 flex-wrap">
        <button name="action" value="confirm" class="bg-green-600 text-white px-4 py-2 rounded-xl">Confirm</button>
        <button name="action" value="under_review" class="bg-amber-500 text-white px-4 py-2 rounded-xl">Mark Under Review</button>
        <button name="action" value="reject" class="bg-red-600 text-white px-4 py-2 rounded-xl">Reject</button>
      </div>
    </form>
    <form method="post" class="mt-4 space-y-2 text-sm" onsubmit="return confirm('Change the donation status?')">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <label class="block text-gray-600">Set status</label>
      <div class="flex gap-2">
        <select name="new_status" class="border rounded-xl px-3 py-2">
          <option value="pending">Pending</option>
          <option value="under_review">Under Review</option>
          <option value="confirmed">Confirmed</option>
          <option value="rejected">Rejected</option>
        </select>
        <input type="hidden" name="action" value="set_status">
        <button class="bg-[#0F5B66] text-white px-4 py-2 rounded-xl">Apply</button>
      </div>
    </form>
    <form method="post" class="mt-6" onsubmit='return confirm(<?= h(json_encode("Are you sure you want to permanently delete this donation?\n\nDonor: " . $donation['name'] . "\nReference: " . $donation['reference'] . "\nAmount: TZS " . number_format((float)$donation['amount'], 2) . "\nStatus: " . $donation['status'])) ?>)'>
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="action" value="delete">
      <button class="bg-red-700 text-white px-4 py-2 rounded-xl text-sm">Delete Donation</button>
    </form>
  </div>
</div>

<div class="bg-white rounded-2xl shadow p-6 mt-6">
  <h2 class="font-bold mb-3">Audit Trail</h2>
  <table class="w-full text-sm"><thead><tr class="text-left text-gray-500"><th>Action</th><th>Old</th><th>New</th><th>Date</th></tr></thead><tbody>
  <?php foreach ($audits as $a): ?>
  <tr class="border-t"><td><?= h($a['action']) ?></td><td><?= h($a['old_value']) ?></td><td><?= h($a['new_value']) ?></td><td><?= h($a['created_at']) ?></td></tr>
  <?php endforeach; ?>
  </tbody></table>
</div>
<?php admin_foot();
