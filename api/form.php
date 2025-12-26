<?php
declare(strict_types=1);

require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../repository.php';

header('Content-Type: application/json');

$formId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($formId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid form id']);
    exit;
}

$data = Repository::getFormById($formId);

if (!$data) {
    http_response_code(404);
    exit(json_encode("Not Found"));
}

$needsCode = (int)$data['form']['requires_code'] === 1;
$hasAccess = !$needsCode || hasFormAccess($formId) || $_SESSION['user_id'] == $data['form']['owner_id'];

if ($hasAccess) {
    echo json_encode([
        'data' => $data,
        'session_test' => [
            'logged_in' => !empty($_SESSION['user_id']),
            'user_id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
        ]
    ]);
} else {
    http_response_code(403);
    exit(json_encode("Code required"));
}

