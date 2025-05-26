<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'includes/db.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    if ($password !== $confirm) {
        $error = "Паролі не співпадають.";
    } else {
        // Перевірка, чи користувач або email вже існує
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Користувач або email вже існує.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $insert->bind_param("sss", $username, $email, $hash);
            if ($insert->execute()) {
                $success = "Успішна реєстрація! Тепер можете увійти.";
            } else {
                $error = "Помилка під час реєстрації.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8">
  <title>Реєстрація | GameHub</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron&display=swap" rel="stylesheet" />
</head>
<body>
<div class="background"></div>
<div class="wrapper">
  <header>
    <div class="logo">🎮 GameHub</div>
    <nav>
      <a href="index.php">Ігри</a>
    </nav>
  </header>

  <main>
    <div class="login-box">
      <h1>Реєстрація</h1>

      <?php if ($error): ?>
        <div class="error-message"><?php echo $error; ?></div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="error-message" style="background: green;"><?php echo $success; ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <input type="text" name="username" placeholder="Логін" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Пароль" required>
        <input type="password" name="confirm" placeholder="Повторіть пароль" required>
        <button type="submit">Зареєструватися</button>
      </form>

      <p>Вже маєш акаунт? <a href="login.php">Увійти</a></p>
    </div>
  </main>

  <footer>
    <p>© 2025 GameHub | Telegram | Контакти</p>
  </footer>
</div>
</body>
</html>
