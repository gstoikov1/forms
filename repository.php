<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

class Repository
{
    private function __construct() {
    }
    public static function getFormById(int $id): array|null
    {
        $pdo = db();

        $stmtForm = $pdo->prepare("SELECT id, name, owner_id, requires_code, code FROM forms WHERE id = ? LIMIT 1");
        $stmtForm->execute([$id]);
        $form = $stmtForm->fetch();
        if (!$form) return null;

        $stmtQuestions = $pdo->prepare("SELECT id, question_text, question_type, question_order FROM questions WHERE form_id = ? ORDER BY question_order ASC");
        $stmtQuestions->execute([$id]);
        $questions = $stmtQuestions->fetchAll();

        $stmtOptions = $pdo->prepare("SELECT id, option_text, option_order, question_id FROM question_options WHERE question_id = ? ORDER BY option_order ASC");

        $optionsByQuestionId = [];
        foreach ($questions as $q) {
            $stmtOptions->execute([(int)$q['id']]);
            $optionsByQuestionId[$q['id']] = $stmtOptions->fetchAll();
        }

        return [
            'form' => $form,
            'questions' => $questions,
            'questionOptions' => $optionsByQuestionId
        ];
    }

    public static function registerUser(string $username, string $pass, string $email): int
    {
        $hash = password_hash($pass, PASSWORD_DEFAULT);

        try {
            $pdo = db();
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hash]);

            $stmt = $pdo->prepare("SELECT id
                                         FROM users
                                         WHERE username = ?");
            $stmt->execute([$username]);
            $row = $stmt->fetch();
            return $row ? (int)$row['id'] : -1;
        } catch (PDOException $e) {
            return -2;
        }
    }

    public static function loginUser(string $login, string $pass): mixed {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT id, username, email, password_hash FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();
        return ($user && password_verify($pass, $user['password_hash'])) ? $user : null;
    }

}
