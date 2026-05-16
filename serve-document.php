<?php
require_once 'includes/config.php';
requireAdmin(); // Admin-only — broker access added in Step 9

$fileParam = trim($_GET['file'] ?? '');

if (empty($fileParam)) {
    http_response_code(404);
    exit('File not found.');
}

// Only accept a bare filename — no directory components
$filename  = basename($fileParam);
$uploadDir = realpath(UPLOAD_DIR);

if ($uploadDir === false) {
    http_response_code(500);
    exit('Upload directory unavailable.');
}

$filePath = realpath($uploadDir . DIRECTORY_SEPARATOR . $filename);

// Path traversal guard
if ($filePath === false || strpos($filePath, $uploadDir . DIRECTORY_SEPARATOR) !== 0) {
    http_response_code(403);
    exit('Access denied.');
}

if (!file_exists($filePath)) {
    http_response_code(404);
    exit('File not found.');
}

// Verify file is registered in application_documents (prevents serving arbitrary upload-dir files)
$stmt = $pdo->prepare(
    "SELECT id FROM application_documents WHERE file_path = ? LIMIT 1"
);
$stmt->execute(['uploads/' . $filename]);
if (!$stmt->fetch()) {
    http_response_code(403);
    exit('Access denied.');
}

// Determine MIME and stream
$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filePath);
finfo_close($finfo);

$allowed = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
if (!in_array($mimeType, $allowed, true)) {
    http_response_code(403);
    exit('Unsupported file type.');
}

header('Content-Type: ' . $mimeType);
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filePath));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-cache');
readfile($filePath);
exit();
