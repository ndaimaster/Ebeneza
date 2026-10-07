# Ebeneza Foundation — Simplification Report

Updated: 2026-10-04

The active system is the simplified donation system described below.
Old Pesapal / webhook / SMS / payment-provider code has been REMOVED
from the active application (kept only outside the web root).

## Architecture

DONOR → DONATION FORM → DB → UNIQUE REFERENCE → PAYMENT INSTRUCTIONS →
DONOR PAYS MANUALLY → ADMIN REVIEWS → CONFIRMED / REJECTED → DONOR TRACKS STATUS

## Initial admin credentials (CHANGE BEFORE PRODUCTION)
- Email: `admin@ebeneza.org`
- Initial password: `Ebeneza@2026!Admin`
- Stored only as `password_hash()` in MySQL. Password must NOT appear
  in source code, JavaScript, or on the public website.

## Payment information
- Lipa Namba: `4109234` (stored in `site_settings.lipa_namba`; editable in Admin -> Settings)
- Organization: `Ebeneza Foundation`
- Bank: NBC Bank, Account 057172000891, SWIFT NLCBTZTX (in `site_settings`)

## URLs
- Homepage:  http://localhost/Ebeneza/index.html
- Donate:    http://localhost/Ebeneza/index.html#donate
- Thank-you: http://localhost/Ebeneza/thank-you.html?ref=EBZ-...
- Tracking:  http://localhost/Ebeneza/track.html
- Contact:   http://localhost/Ebeneza/contact.html
- Admin login:     http://localhost/Ebeneza/admin/login.php
- Admin dashboard: http://localhost/Ebeneza/admin/dashboard.php  (`/admin/` redirects)

## Database tables (ebeneza_foundation)
- admins
- donations            — id, reference(UNIQUE), name, email, phone, amount(DECIMAL 15,2),
                        status(pending|under_review|confirmed|rejected), created_at, updated_at
- site_settings
- audit_logs
- contact_messages
- documents
- impact_statistics
- gallery
- partners
- testimonials

Indexes: donations.status, donations.email, donations.name, donations.reference.
Foreign keys: impact_statistics.source_document_id -> documents(id) ON DELETE SET NULL,
audit_logs.admin_id -> admins(id) ON DELETE SET NULL.

## Files changed (summary)
- database/schema.sql, database/seed.sql
- config/app.php, config/database.php (env-driven DB_NAME)
- includes/helpers.php (current_admin() reads id,name,email; removed notify/send_sms)
- services/DonationService.php (simple create/findByReference)
- api/donations/create.php, api/donations/status.php (simplified validation + public payload)
- api/gallery.php, api/partners.php, api/testimonials.php (NEW public endpoints)
- admin/dashboard.php, admin/donations.php, admin/donation.php, admin/settings.php, admin/impact.php, admin/gallery.php, admin/partners.php, admin/testimonials.php, admin/index.php
- index.html (simplified donate form, gallery/partners/testimonials fetch+render, canonical/OG)
- js/donation.js, js/validation.js
- images placeholders in hero/about sections replaced with logo
- robots.txt, sitemap.xml updated
- .env / .env.example trimmed to APP_*/DB_*/MAIL_*

## Files removed / deactivated
Web root:
- api/webhooks/payment.php            — REMOVED (HMAC reconciliation flow deleted)
- api/payments/manual-confirmation.php — REMOVED
- api/donation-categories.php          — REMOVED
- api/payments/, api/webhooks/         — moved to C:\xampp\legacy-ebeneza\
- services/PaymentMatchingService.php  — REMOVED (archived to legacy-ebeneza)
- services/NotificationService.php     — REMOVED (broken require path; superseded)
- payments/                            — moved to C:\xampp\legacy-ebeneza\payments
- database/schema.sql (old complex version) — archived as schema-complex-legacy.sql
- donation-thankyou.html (old interactive status page) — deleted
- legacy/ → moved outside web root to C:\xampp\legacy-ebeneza

Payment code (Pesapal provider, SMS forwarder/HMAC webhooks, PaymentMatchingService,
confirmation matching, admin-side transaction reconciliation) is archived outside the
web root and cannot be executed.

## Database
Database: ebeneza_foundation (utf8mb4, InnoDB)
Tables:
- admins(id, name, email, password_hash, created_at, updated_at, last_login_at)
- donations(id, reference UNIQUE, name, email, phone, amount DECIMAL(15,2),
            status ENUM(pending|under_review|confirmed|rejected), created_at, updated_at)
- site_settings(setting_key PK, setting_value, updated_at)
- audit_logs(id, admin_id FK->admins ON DELETE SET NULL, action, entity_type, entity_id,
             old_value, new_value, ip_address, user_agent, created_at)
- contact_messages, documents, impact_statistics, gallery, partners, testimonials
- REMOVED tables: admin_sessions, donation_categories, payment_transactions,
  payment_webhooks, notifications

Duplicate `reference` insert is rejected by MySQL (verified, ERROR 1062).

## Tests executed (this pass, live)
- PHP lint on every PHP file via `php -l` — all PASS
- Admin login (valid + invalid password) — PASS
- Admin access control: unauthenticated access redirects to login — PASS
- CSRF: admin POST without token → status unchanged — PASS
- Session: ID regenerated on login confirm; idle expiry implemented (code-verified)
- Donation create: valid → 200 reference EBZ-YYYY-XXXXXX status pending; email attempt non-blocking
- Validation: empty name/invalid email/0/negative/non-numeric/oversize amount → 422/400 as applicable — PASS
- SQLi/XSS in donor name/email stored literally / escaped on output (admin + API JSON)
- Donation visible on admin donations list/receipt flow; confirm→confirmed; reject w/ reason → rejected; without reason → rejected NOT applied
- status.php: unknown/invalid reference → safe error; returns only reference/amount/currency/date/status (no PII leak)
- Docker not required.

## Critical-flow gaps found during testing and FIXED
1. `admin/gallery.php` thumbnails used root-absolute `/uploads/...` → 404 under subdirectory → fixed to `url($r['image_path'])`.
2. No public gallery API/rendering existed → added `api/gallery.php`, wired into `index.html`.
3. No public API for partners/testimonials → added `api/partners.php`, `api/testimonials.php`, homepage sections.
4. No publish/unpublish action on gallery/partners/testimonials/impact → toggle actions added (+ audit logs where service existed).
5. `admin/settings.php` crashed with `FETCH_KEY_PAIR` (3-column row) — fixed.
6. `admin/index.php` crashed (undefined `url()`) — fixed; route by auth state.
7. `admin/impact.php` `source_document_id` undefined-key warning clarified.

## Production deployment requirements
- HTTPS + `APP_ENV=production`, real SMTP (MAIL_*), strong DB password
- Run `php scripts/create-admin.php` for the first admin; change seeded password immediately
- Keep `.htaccess` honored (AllowOverride All) → verify build: GET /Ebeneza/.env should be 403
- Keep `C:\xampp\legacy-ebeneza` OUTSIDE the web root
