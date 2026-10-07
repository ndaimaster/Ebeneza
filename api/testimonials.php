<?php
require_once __DIR__ . '/../includes/helpers.php';
$rows = db()->query("SELECT title, beneficiary_label, location, situation, intervention, outcome, case_date, image_path FROM testimonials WHERE published = 1 ORDER BY case_date DESC, created_at DESC")->fetchAll();
json_response(['testimonials' => $rows]);
