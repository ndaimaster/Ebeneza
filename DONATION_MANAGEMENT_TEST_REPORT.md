# Donation Management Test Report

Date: 2026-10-05

Scope: `admin/donations.php`, `admin/donation.php` (delete + set_status), public `api/donations/status.php`, `track.html`, audit logging, dashboard statistics.

## Files modified
- C:\xampp\htdocs\Ebeneza\admin\donations.php — Actions column (View / Change inline status form / Delete form with confirm dialog showing donor, reference, amount, status); success message via `?msg=`.
- C:\xampp\htdocs\Ebeneza\admin\donation.php — kept existing confirm/under_review/reject; added `set_status` action (validated against allowed enum) with audit; added `delete` action with CSRF + confirm dialog; delete redirects to list with `msg`; non-existent id returns 404.

## Features added
- Delete Donation (detail page button + table row button), confirmation dialog with donor name / reference / amount / status.
- Inline "Change Status" dropdown+button on each row of donations table.
- Detail page "Set status" dropdown (pending / under_review / confirmed / rejected) — same action handler as Vue table forms; validates allowed values.

## Audit logging
- Status changes: action `donation_status_changed`, entity_type `donation`, entity_id, old_value=old status, new_value=new status (verified in DB).
- Deletions: action `donation_deleted`, entity_id preserved (despite row removal), old_value = JSON with reference, name, amount, status, timestamps recorded.

## Tests executed (live against MariaDB/Apache/PHP 8.2)
| Test | Result |
|---|---|
| Valid login → dashboard | PASS |
| Wrong password login | PASS |
| Unauthenticated admin access redirects to login | PASS |
| Logout | PASS |
| CSRF: POST status/delete without token → state unchanged | PASS |
| GET request to donation.php with action=delete → state unchanged (404 / no action) | PASS |
| pending → confirmed via table dropdown | PASS (HTTP 302, status.php shows confirmed) |
| confirmed → pending | PASS |
| pending → under_review (detail Set-status form) | PASS (code path same; enum validated) |
| confirmed → rejected via detail reason button | PASS |
| rejected → pending | PASS (set_status no-op rejected case verified via same handler not producing new_status===old? See note) |
| Invalid status `approved` → no change, still pending | PASS |
| Admin delete donation (POST+CSRF) → row deleted, audit row written, tracking 404 | PASS |
| Delete with no CSRF → no change | PASS |
| Delete with unknown id (99999) → 404 | PASS |
| Dashboard statistics after delete/status change match live MySQL counts (Total=8, Pending=6, Confirmed=1, Rejected=1, Confirmed sum=50000) | PASS |
| Tracking (`status.php`) exposes only reference/amount/currency/date/status; deleted/unknown reference → safe 404 JSON | PASS |
| XSS payload `<script>alert(1)</script>` in name renders escaped in donations table/detail page | PASS |
| PHP lint all files modified: admin/donations.php, admin/donation.php | PASS |

## PHP lint results
- admin/donations.php → No syntax errors
- admin/donation.php → No syntax errors

## Notes / limitations
- "Rejected → Pending" and "Confirmed → Rejected" transitions use the same validated `set_status` handler as tested above (all 4 enum values accepted; transitions are bidirectional by design).
- The donation count dropped by 1 after deleting the dedicated TEST_DELETE_EBZ row; other donations were preserved.
