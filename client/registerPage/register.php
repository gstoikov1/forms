<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../session.php';
require_once __DIR__ . '/../../repository.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $email    = trim($_POST['email'] ?? '');
  $pass     = $_POST['password'] ?? '';

  if ($username === '' || strlen($username) < 3) {
    $error = "Username must be at least 3 characters.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Invalid email address.";
  } elseif (strlen($pass) < 8) {
    $error = "Password must be at least 8 characters.";
  } else {
    $res = Repository::registerUser($username, $pass, $email);
    if ($res > 0) {      // Auto-login after registration
      $_SESSION['user_id'] = $res;
      $_SESSION['username'] = $username;

      session_regenerate_id(true);

      header("Location: /forms/dashboard.php");
      exit;
    } else if ($res == -1) {
        $error = "Internal Server Error";
    } else if ($res == -2) {
        $error = "Username or email already exists.";

    } else {
        $error = "Unknown error";
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Register - PuffinForms</title>
    <link rel="stylesheet" href="/Forms-php/client/index.css">
    <link rel="stylesheet" href="/Forms-php/client/error.css">
    <link rel="stylesheet" href="/Forms-php/client/registerPage/register.css">
</head>
<body>

<div class="page-container">
    <div class="register-card">
        <h1>Register</h1>

        <?php if ($error): ?>
            <p class="error-msg"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <input name="username" type="text" placeholder="Username" required>
            </div>
            <div class="form-group">
                <input name="email" type="email" placeholder="Email Address" required>
            </div>
            <div class="form-group">
                <input name="password" type="password" placeholder="Password" required>
            </div>
            
            <button type="submit" class="btn-register">Create Account</button>
        </form>

        <a href="/Forms-php/client/loginPage/login.php" class="login-link">
            Already have an account? <span>Login</span>
        </a>
    </div>
</div>

</body>
</html>
