<?php
require_once __DIR__ . '/../includes/helpers.php';
// Public-safe settings only (no secrets)
$allowed = ['org_name','org_registration','org_address','org_email','org_whatsapp','bank_name','bank_account_name','bank_account_number','bank_swift','lipa_namba','lipa_namba_label','social_facebook','social_instagram','social_youtube','social_linkedin','social_x'];
$in = implode(',', array_fill(0, count($allowed), '?'));
$stmt = db()->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ($in)");
$stmt->execute($allowed);
json_response(['settings' => $stmt->fetchAll(PDO::FETCH_KEY_PAIR)]);
