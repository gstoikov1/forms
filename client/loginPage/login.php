<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../session.php';
require_once __DIR__ . '/../../repository.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $login = trim($_POST['login'] ?? ''); // username OR email
  $pass  = $_POST['password'] ?? '';
  $user = Repository::loginUser($login, $pass);
  if ($user) {
      $_SESSION['user_id'] = (int)$user['id'];
      $_SESSION['username'] = $user['username'];

      session_regenerate_id(true);

      header("Location: /Forms-php/dashboard.php");
      exit;
  } else {
      $error = "Invalid username or password.";
  }

}
?>

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Login</title>
    <link rel="stylesheet" href="/Forms-php/client/index.css">
    <link rel="stylesheet" href="/Forms-php/client/error.css">
    <link rel="stylesheet" href="/Forms-php/client/loginPage/login.css">
</head>
<body>

<div class="page-container">
    <div class="login-card">
        <h1>PuffinForms</h1>

        <?php if ($error): ?>
            <p class="error-msg"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <input name="login" type="text" placeholder="Username or Email" required>
            </div>
            <div class="form-group">
                <input name="password" type="password" placeholder="Password" required>
            </div>
            <button type="submit" class="btn-login">Login</button>
        </form>

        <a href="/Forms-php/client/registerPage/register.php" class="register-link">
            Not a member? <span>Sign up now</span>
        </a>
    </div>
</div>

</body>
</html>
