<?php
require_once __DIR__ . '/../includes/helpers.php';
$rows = db()->query("SELECT title, description, image_path, category, activity_date FROM gallery WHERE published = 1 ORDER BY activity_date DESC, created_at DESC")->fetchAll();
json_response(['gallery' => $rows]);
