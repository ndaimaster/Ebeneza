<?php
require_once __DIR__ . '/../includes/helpers.php';
$stmt = db()->query("SELECT metric_name, value, unit, year, description, source_title FROM impact_statistics WHERE published = 1 AND verified = 1 ORDER BY year DESC, metric_name");
json_response(['impact' => $stmt->fetchAll()]);
