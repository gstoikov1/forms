<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../session.php';

header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

$formId = (int)($body['form_id'] ?? 0);
$code = trim((string)($body['code'] ?? ''));

if ($formId <= 0 || strlen($code) !== 5) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$pdo = db();
$stmt = $pdo->prepare("SELECT code, requires_code FROM forms WHERE id = ? LIMIT 1");
$stmt->execute([$formId]);
$form = $stmt->fetch();

if (!$form) {
    http_response_code(404);
    echo json_encode(['error' => 'Form not found']);
    exit;
}

if ((int)$form['requires_code'] !== 1) {
    // no code needed → grant access anyway
    grant_form_access($formId);
    echo json_encode(['ok' => true]);
    exit;
}

// Compare safely (avoid timing leaks)
$expected = (string)$form['code'];
if (!hash_equals($expected, $code)) {
    http_response_code(403);
    echo json_encode(['error' => 'Wrong code']);
    exit;
}

// Success: mark this session as allowed
grantFormAccess($formId);

echo json_encode(['ok' => true]);
