<?php

require_once __DIR__ . '/../repository.php';

header('Content-Type: application/json');
error_reporting(0);

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$error = "";
$answers = $data['answers'] ?? [];
$formId = $data['form_id'] ?? -1;

if ($formId == -1 || !Repository::formExistsById($formId)) {
    http_response_code(400);
    $error = "Form does not exist";
    echo json_encode(["error" => $error]);
    exit;
}
if (!$answers) {
    http_response_code(400);
    $error = "No answers given";
    echo json_encode(["error" => $error]);
    exit;
}
$mapQuestionIdToAnswer = [];
foreach ($answers as $answer) {
    $value = $answer['value'] ?? $answer['option_id'] ?? $answer['option_ids'];
    if (!$value) {
        $error = "Question with id {$answer['question_id']} does not have an answer";
        echo json_encode(["error" => $error]);
        exit;
    }
    $type = $answer['type'];
    $mapQuestionIdToAnswer[$answer['question_id']] = $value;



}

echo json_encode([$mapQuestionIdToAnswer]);