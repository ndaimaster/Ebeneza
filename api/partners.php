<?php
require_once __DIR__ . '/../includes/helpers.php';
$rows = db()->query("SELECT name, logo_path, description, website_url FROM partners WHERE published = 1 ORDER BY created_at DESC")->fetchAll();
json_response(['partners' => $rows]);
