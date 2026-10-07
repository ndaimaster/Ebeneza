# Ebeneza Foundation — Website & Donation System (Simplified)

PHP 8 + MySQL NGO website with a **simple** donation flow:

DONOR → DONATION FORM → DATABASE → UNIQUE REFERENCE → PAYMENT INSTRUCTIONS → DONOR PAYS MANUALLY → ADMIN REVIEWS (confirm/reject) → DONOR TRACKS STATUS

There is **no automatic payment confirmation** in this version — no Pesapal, no payment webhooks,
no SMS reconciliation, and no payment-provider adapters. Admins review donations manually.

## Requirements
- XAMPP (Apache + PHP 8+ + MySQL 8+/MariaDB 10.4+)
- PDO MySQL driver, curl, fileinfo (bundled with XAMPP)

## Installation (XAMPP)
1. Copy this folder to `C:\xampp\htdocs\Ebeneza`.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. In phpMyAdmin, create database `ebeneza_foundation` (utf8mb4).
4. Import `database/schema.sql`, then `database/seed.sql`.
5. Copy `.env.example` to `.env` and fill in your values.
6. Visit `http://localhost/Ebeneza/index.html` — admin at `http://localhost/Ebeneza/admin/login.php`.
7. **Initial admin login:** Email `admin@ebeneza.org`, Password `Ebeneza@2026!Admin` — **change this password before any production deployment.**
8. Store the Lipa Namba / bank details in `Admin → Settings` (they are seeded, editable, and not hard-coded in the UI).

## Pages
- Homepage: `index.html`
- Donation form: `index.html#donate`
- Thank-you / payment instructions: `thank-you.html?ref=EBZ-...`
- Tracking: `track.html`
- Contact: `contact.html`
- Admin login: `admin/login.php`
- Admin dashboard: `admin/dashboard.php` (also `/admin/`)

## Database tables
`admins`, `donations`, `site_settings`, `audit_logs`, `contact_messages`,
`documents`, `impact_statistics`, `gallery`, `partners`, `testimonials`.

The core `donations` table: `id, reference, name, email, phone, amount, status, created_at, updated_at`.
Statuses: `pending, under_review, confirmed, rejected`.

## Security
PDO prepared statements, `password_hash()`/`password_verify()`, CSRF checks on all admin
actions, secure session cookies, session ID regeneration on login, idle session expiry,
IP-based rate limiting on login and donation creation, XSS-safe output escaping, and an
`audit_logs` trail for admin actions.

## Legacy
The old Node.js Pesapal server lives OUTSIDE the web root at `C:\xampp\legacy-ebeneza`.
The old payment-provider PHP code (`payments/`, `api/webhooks/`, `api/payments/`,
`services/PaymentMatchingService.php`, and the complex legacy schema) has been moved there
too — it is not part of the active application and cannot be executed from the website.

## Needs verification from Ebeneza Foundation
- Confirm registration details (01NGO/R/9396) with the NGO Registration Board.
- Confirm the current Lipa Namba (`4109234`) and NBC Bank details in `Admin → Settings`.
- Provide real impact figures and documents via the admin dashboard.
