<?php
require_once __DIR__ . '/_layout.php';
$admin = require_admin();
$pdo = db();

$totalDonations = $pdo->query("SELECT COUNT(*) FROM donations")->fetchColumn();
$pending = $pdo->query("SELECT COUNT(*) FROM donations WHERE status='pending'")->fetchColumn();
$underReview = $pdo->query("SELECT COUNT(*) FROM donations WHERE status='under_review'")->fetchColumn();
$confirmed = $pdo->query("SELECT COUNT(*) FROM donations WHERE status='confirmed'")->fetchColumn();
$rejected = $pdo->query("SELECT COUNT(*) FROM donations WHERE status='rejected'")->fetchColumn();
$confirmedAmount = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM donations WHERE status='confirmed'")->fetchColumn();
$recent = $pdo->query("SELECT id, reference, name, email, amount, status, created_at FROM donations ORDER BY created_at DESC LIMIT 8")->fetchAll();

admin_head('Dashboard');
?>
<h1 class="text-2xl font-bold text-[#0F5B66] mb-6">Welcome, <?= h($admin['name']) ?></h1>
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
  <?php foreach ([
    ['Total Donations', $totalDonations, 'fa-hand-holding-heart text-[#0F5B66]'],
    ['Pending', $pending, 'fa-clock text-amber-600'],
    ['Confirmed', $confirmed, 'fa-circle-check text-green-600'],
    ['Rejected', $rejected, 'fa-circle-xmark text-red-600'],
  ] as $card): ?>
  <div class="bg-white rounded-2xl shadow p-5"><i class="fa-solid <?= $card[2] ?>"></i><p class="text-2xl font-extrabold mt-2"><?= h($card[1]) ?></p><p class="text-sm text-gray-500"><?= h($card[0]) ?></p></div>
  <?php endforeach; ?>
</div>

<div class="bg-white rounded-2xl shadow p-6 mb-6">
  <h2 class="font-bold text-lg mb-1">Total Confirmed Amount</h2>
  <p class="text-3xl font-extrabold text-[#0F5B66]">TZS <?= number_format((float)$confirmedAmount, 2) ?></p>
  <?php if ($underReview): ?><p class="text-sm text-amber-600 mt-2"><?= (int)$underReview ?> donation(s) are under review.</p><?php endif; ?>
</div>

<div class="bg-white rounded-2xl shadow p-6 mt-6">
  <h2 class="font-bold text-lg mb-3">Recent Donations</h2>
  <table class="w-full text-sm"><thead><tr class="text-left text-gray-500"><th>Reference</th><th>Donor</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead><tbody>
  <?php foreach ($recent as $r): ?>
    <tr class="border-t"><td><a class="text-[#0F5B66] font-semibold" href="<?= url('admin/donation.php?id=') ?><?= (int)$r['id'] ?>"><?= h($r['reference']) ?></a></td>
    <td><?= h($r['name']) ?> (<?= h($r['email']) ?>)</td>
    <td><?= h('TZS ' . number_format((float)$r['amount'], 2)) ?></td>
    <td><?= h($r['status']) ?></td><td><?= h($r['created_at']) ?></td></tr>
  <?php endforeach; ?>
  </tbody></table>
</div>
<?php admin_foot();
