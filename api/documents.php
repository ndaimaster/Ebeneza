<?php
require_once __DIR__ . '/../includes/helpers.php';
$stmt = db()->query("SELECT title, document_type, description, file_path, year, created_at FROM documents WHERE published = 1 ORDER BY year DESC, title");
json_response(['documents' => $stmt->fetchAll()]);
