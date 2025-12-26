<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../session.php';

$userId = require_login_json();

header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data) || empty($data['form']) || empty($data['questions'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload']);
    exit;
}

$form = $data['form'];
$questions = $data['questions'];

$name = trim((string)($form['name'] ?? ''));
$requiresCode = (int)($form['requires_code'] ?? 0);
$code = $form['code'] ?? null;

if ($name === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Form name is required']);
    exit;
}
if ($requiresCode === 1) {
    $code = trim((string)$code);
    if (strlen($code) !== 5) {
        http_response_code(422);
        echo json_encode(['error' => 'Code must be exactly 5 characters']);
        exit;
    }
} else {
    $code = null;
}

$pdo = db();

try {
    $pdo->beginTransaction();

    // Insert form
    $stmt = $pdo->prepare("INSERT INTO forms (name, requires_code, code, owner_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $requiresCode, $code, $userId]);
    $formId = (int)$pdo->lastInsertId();

    // Prepare statements once
    $qStmt = $pdo->prepare("
    INSERT INTO questions (form_id, question_text, question_type, question_order)
    VALUES (?, ?, ?, ?)
  ");

    $optStmt = $pdo->prepare("
    INSERT INTO question_options (question_id, option_text, option_order)
    VALUES (?, ?, ?)
  ");

    foreach ($questions as $q) {
        $qText = trim((string)($q['question_text'] ?? ''));
        $qType = (string)($q['question_type'] ?? '');
        $qOrder = (int)($q['question_order'] ?? 0);

        if ($qText === '' || $qOrder < 1) {
            throw new RuntimeException("Invalid question text/order");
        }
        if (!in_array($qType, ['OPEN', 'SINGLE_CHOICE', 'MULTI_CHOICE'], true)) {
            throw new RuntimeException("Invalid question type");
        }

        $qStmt->execute([$formId, $qText, $qType, $qOrder]);
        $questionId = (int)$pdo->lastInsertId();

        // Options only for choice questions
        if ($qType !== 'OPEN') {
            $options = $q['options'] ?? [];
            if (!is_array($options) || count($options) < 2) {
                throw new RuntimeException("Choice questions need at least 2 options");
            }
            foreach ($options as $opt) {
                $optText = trim((string)($opt['option_text'] ?? ''));
                $optOrder = (int)($opt['option_order'] ?? 0);
                if ($optText === '' || $optOrder < 1) {
                    throw new RuntimeException("Invalid option text/order");
                }
                $optStmt->execute([$questionId, $optText, $optOrder]);
            }
        }
    }

    $pdo->commit();

    echo json_encode(['ok' => true, 'form_id' => $formId]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['error' => 'Could not create form']);
}
