<?php
// Validates and stores an uploaded file with a randomized name.
// Returns the web-relative path (e.g. uploads/documents/abcd.pdf) or null on failure.
function save_upload(string $field, string $destDir, array $allowedExt, int $maxBytes): ?string {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
    if ($_FILES[$field]['size'] > $maxBytes) return null;
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) return null;
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES[$field]['tmp_name']);
    if ($ext === 'pdf' && $mime !== 'application/pdf') return null;
    if (in_array($ext, ['png','jpg','jpeg','webp'], true) && !str_starts_with((string)$mime, 'image/')) return null;
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);
    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    $target = $destDir . '/' . $name;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $target)) return null;
    $root = str_replace('\\', '/', realpath(dirname(__DIR__)));
    return str_replace('\\', '/', substr(realpath($target), strlen($root) + 1));
}
