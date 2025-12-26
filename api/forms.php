<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../session.php';

//header('Content-Type: application/json');

$userId = require_login_json();

$pdo = db();

$stmt = $pdo->prepare("SELECT name, requires_code, code, owner_id, id
                       FROM forms
                       WHERE owner_id = ? ");
$stmt->execute([$userId]);

$forms = $stmt->fetchAll();

echo json_encode([
  'forms' => $forms
]);