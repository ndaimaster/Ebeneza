<?php
require_once __DIR__ . '/includes/helpers.php';
$ref = trim($_GET['ref'] ?? '');
if ($ref === '' || !preg_match('/^EBZ-\d{4}-[A-Z0-9]{6}$/', $ref)) { http_response_code(404); echo 'Receipt not available for this reference.'; exit; }
$stmt = db()->prepare("SELECT * FROM donations WHERE reference=? AND status='confirmed'");
$stmt->execute([$ref]);
$d = $stmt->fetch();
if (!$d) { http_response_code(404); echo 'Receipt not available for this reference.'; exit; }
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Donation Receipt <?= h($d['reference']) ?> | Ebeneza Foundation</title>
<style>body{font-family:Georgia,serif;max-width:720px;margin:40px auto;padding:0 16px;color:#222}@media print{button{display:none}}</style>
</head><body>
<h1>Ebeneza Foundation — Donation Receipt</h1>
<p translate="no"><em>This receipt confirms a donation. It is not a tax deduction certificate unless Ebeneza Foundation confirms your eligibility.</em></p>
<table style="border-collapse:collapse;width:100%">
<?php foreach ([
  'Donation reference' => $d['reference'],
  'Donor name' => $d['name'],
  'Amount' => 'TZS ' . number_format((float)$d['amount'], 2),
  'Status' => $d['status'],
  'Registered at' => $d['created_at'],
  'Confirmed/updated at' => $d['updated_at'],
] as $k => $v): ?>
<tr><td style="border:1px solid #ccc;padding:8px;font-weight:bold"><?= h($k) ?></td><td style="border:1px solid #ccc;padding:8px"><?= h($v) ?></td></tr>
<?php endforeach; ?>
</table>
<p>Ebeneza Foundation · Registered NGO: 01NGO/R/9396 · P.O. Box 12597, Arusha, Tanzania · ebenezafoundation2025@gmail.com · +255 753 411 688</p>
<button onclick="window.print()">Print / Save as PDF</button>
</body></html>
