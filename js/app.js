// Mobile navigation + scroll reveal + settings/stats/documents loading
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('mobileMenuBtn');
    const drawer = document.getElementById('mobileMenu');
    const closeBtn = document.getElementById('mobileMenuClose');
    const icon = btn ? btn.querySelector('i') : null;

    const openMenu = () => {
        drawer.classList.remove('hidden');
        drawer.classList.add('flex');
        if (icon) { icon.classList.remove('fa-bars'); icon.classList.add('fa-xmark'); }
        btn.setAttribute('aria-expanded','true');
    };
    const closeMenu = () => {
        drawer.classList.add('hidden');
        drawer.classList.remove('flex');
        if (icon) { icon.classList.remove('fa-xmark'); icon.classList.add('fa-bars'); }
        btn.setAttribute('aria-expanded','false');
    };

    if (btn && drawer && closeBtn) {
        btn.addEventListener('click', () => {
            if (drawer.classList.contains('hidden')) openMenu();
            else closeMenu();
        });
        closeBtn.addEventListener('click', closeMenu);
        document.addEventListener('click', (e) => {
            if (!drawer.contains(e.target) && e.target !== btn && !btn.contains(e.target)) closeMenu();
        });
        drawer.querySelectorAll('a').forEach(a => a.addEventListener('click', closeMenu));
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeMenu(); });
    }
    const nav = document.getElementById('siteNav');
    // Navbar is intentionally static on scroll; do not modify its background here.
    const els = document.querySelectorAll('.reveal');
    const obs = new IntersectionObserver(entries => entries.forEach(e => e.target.classList.toggle('reveal-visible', e.isIntersecting)), { threshold: 0.15 });
    els.forEach(el => obs.observe(el));
});

async function fetchJSON(url, opts) {
    const res = await fetch(url, opts);
    const data = await res.json().catch(() => ({}));
    return { ok: res.ok, status: res.status, data };
}
