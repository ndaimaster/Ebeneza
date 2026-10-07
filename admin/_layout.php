<?php
require_once __DIR__ . '/../includes/helpers.php';

function admin_pending_donations_count(): int {
    static $count = null;
    if ($count === null) {
        try {
            $stmt = db()->prepare("SELECT COUNT(*) FROM donations WHERE status = 'pending'");
            $stmt->execute();
            $count = (int)$stmt->fetchColumn();
        } catch (Throwable $e) {
            $count = 0;
        }
    }
    return $count;
}

function admin_head(string $title): void {
$pendingNotifications = admin_pending_donations_count();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($title) ?> | Ebeneza Foundation Admin</title>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>body{font-family:'Inter',sans-serif} h1,h2,h3{font-family:'Poppins',sans-serif}
@keyframes bellPop{0%{transform:scale(1)}40%{transform:scale(1.18)}70%{transform:scale(0.96)}100%{transform:scale(1)}}
@keyframes badgePop{0%{transform:scale(0)}60%{transform:scale(1.25)}100%{transform:scale(1)}}
.bell-bell{transform-origin:center;}
.bell-animate{animation:bellPop 0.6s ease-out 1;}
.badge-animate{animation:badgePop .45s ease-out 1;}
@media (prefers-reduced-motion: reduce){.bell-animate,.badge-animate{animation:none !important}}
</style>
</head><body class="bg-slate-100">
<nav class="bg-[#0F5B66] text-white px-6 py-4 flex flex-wrap gap-4 items-center">
  <a href="<?= url('admin/dashboard.php') ?>" class="font-bold text-lg">Ebeneza Admin</a>
  <a href="<?= url('admin/dashboard.php') ?>" class="hover:text-[#F4B942]">Dashboard</a>
  <a href="<?= url('admin/donations.php') ?>" class="hover:text-[#F4B942]">Donations</a>
  <a href="<?= url('admin/impact.php') ?>" class="hover:text-[#F4B942]">Impact</a>
  <a href="<?= url('admin/documents.php') ?>" class="hover:text-[#F4B942]">Documents</a>
  <a href="<?= url('admin/gallery.php') ?>" class="hover:text-[#F4B942]">Gallery</a>
  <a href="<?= url('admin/partners.php') ?>" class="hover:text-[#F4B942]">Partners</a>
  <a href="<?= url('admin/testimonials.php') ?>" class="hover:text-[#F4B942]">Testimonials</a>
  <a href="<?= url('admin/settings.php') ?>" class="hover:text-[#F4B942]">Settings</a>
  <a href="<?= url('index.html') ?>" class="hover:text-[#F4B942]">Back to Home</a>
  <div class="relative ml-auto" id="adminNotificationBell">
    <button id="bellBtn" type="button" class="relative text-white focus:outline-none focus:ring-2 focus:ring-[#F4B942] rounded-full p-1" aria-label="Notifications" aria-expanded="false" aria-haspopup="true">
      <i id="bellIcon" class="fa-solid fa-bell text-xl <?= $pendingNotifications > 0 ? 'bell-animate' : '' ?>"></i>
      <?php if ($pendingNotifications > 0): ?>
        <span id="bellBadge" class="absolute -top-2 -right-2 bg-red-600 text-white text-xs font-bold rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1 badge-animate"><?= (int)$pendingNotifications ?></span>
      <?php endif; ?>
    </button>
    <div id="bellDropdown" class="hidden absolute right-0 mt-2 w-80 max-w-[90vw] bg-white text-gray-800 rounded-2xl shadow-xl border border-gray-100 p-4 z-50" role="menu" aria-label="Notifications panel">
      <h3 class="font-bold text-[#0F5B66] mb-3">Notifications</h3>
      <?php if ($pendingNotifications > 0): ?>
      <a href="<?= url('admin/donations.php?status=pending') ?>" class="block rounded-xl border border-gray-100 p-3 hover:bg-slate-50 transition">
        <p class="font-semibold text-sm"><?= (int)$pendingNotifications ?> Pending Donation<?= $pendingNotifications === 1 ? '' : 's' ?></p>
        <p class="text-xs text-gray-500 mt-1">Need admin review</p>
        <span class="inline-block mt-2 text-[#0F5B66] text-xs font-semibold">View Donations →</span>
      </a>
      <?php else: ?>
      <p class="text-sm text-gray-500 flex items-center gap-2"><i class="fa-solid fa-circle-check text-green-600"></i> You're all caught up.</p>
      <?php endif; ?>
    </div>
  </div>
  <a href="<?= url('admin/logout.php') ?>" class="text-[#F4B942] font-semibold">Logout</a>
</nav>
<main class="max-w-6xl mx-auto p-6">
<script>
(function() {
  const root = document.getElementById('adminNotificationBell');
  if (!root) return;
  const btn = document.getElementById('bellBtn');
  const icon = document.getElementById('bellIcon');
  const badge = document.getElementById('bellBadge');
  const dd = document.getElementById('bellDropdown');
  const close = () => { dd.classList.add('hidden'); btn.setAttribute('aria-expanded','false'); };
  const toggle = () => { const hidden = dd.classList.toggle('hidden'); btn.setAttribute('aria-expanded', hidden ? 'false' : 'true'); };
  btn.addEventListener('click', (e) => { e.stopPropagation(); toggle(); });
  document.addEventListener('click', (e) => { if (!root.contains(e.target)) close(); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') { close(); btn.focus(); } });
  // After the one-time pop finishes, clean up so it is not replayed on interaction
  const done = () => { icon && icon.classList.remove('bell-animate'); badge && badge.classList.remove('badge-animate'); };
  if (icon) icon.addEventListener('animationend', () => icon.classList.remove('bell-animate'), { once: true });
  if (badge) badge.addEventListener('animationend', () => badge.classList.remove('badge-animate'), { once: true });
})();
</script>
<?php }

function admin_foot(): void { echo '</main></body></html>'; }
