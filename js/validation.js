function validateDonationForm(data) {
    const errors = [];
    if (!data.name || !data.name.trim()) errors.push('Full name is required.');
    if (!data.email || !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(data.email)) errors.push('Enter a valid email address.');
    const amount = Number(data.amount);
    if (!data.amount || isNaN(amount)) errors.push('Amount must be numeric.');
    else if (amount <= 0) errors.push('Amount must be greater than zero.');
    else if (amount > 500000000) errors.push('Amount looks too large.');
    return errors;
}
