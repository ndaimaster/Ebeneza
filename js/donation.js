// Donation flow: fill simple form -> backend -> payment instructions page
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('donationForm');
    const errBox = document.getElementById('donationError');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        errBox.classList.add('hidden');
        const fd = new FormData(form);
        const data = Object.fromEntries(fd.entries());
        const errors = validateDonationForm(data);
        if (errors.length) {
            errBox.textContent = errors.join(' ');
            errBox.classList.remove('hidden');
            return;
        }
        const res = await fetchJSON('api/donations/create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        });
        if (!res.ok) {
            errBox.textContent = res.data.error || 'Could not register the donation.';
            errBox.classList.remove('hidden');
            return;
        }
        window.location.href = 'thank-you.html?ref=' + encodeURIComponent(res.data.reference);
    });
});
