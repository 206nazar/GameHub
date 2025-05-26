<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'includes/db.php';

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $query = $conn->prepare("SELECT id, password_hash FROM users WHERE username = ?");
    $query->bind_param("s", $username);
    $query->execute();
    $query->store_result();

    if ($query->num_rows == 1) {
        $query->bind_result($user_id, $password_hash);
        $query->fetch();
        if (password_verify($password, $password_hash)) {
            $_SESSION['user_id'] = $user_id;
            header("Location: profile.php");
            exit();
        } else {
            $error = "Невірний пароль.";
        }
    } else {
        $error = "Користувача не знайдено.";
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8">
  <title>Вхід | GameHub</title>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
  <style>
    .login-box {
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(10px);
      max-width: 400px;
      margin: 80px auto;
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 0 20px rgba(0,0,0,0.5);
      color: white;
    }

    .login-box h1 {
      font-size: 26px;
      color: #00eaff;
      margin-bottom: 20px;
    }

    .login-box input {
      width: 100%;
      padding: 12px;
      margin: 10px 0;
      border: none;
      border-radius: 8px;
      font-size: 16px;
    }

    .login-box button {
      background: linear-gradient(to right, #00eaff, #0072ff);
      border: none;
      padding: 12px;
      width: 100%;
      font-size: 16px;
      font-weight: bold;
      color: black;
      border-radius: 8px;
      cursor: pointer;
      transition: 0.2s;
    }

    .login-box button:hover {
      transform: scale(1.05);
    }

    .login-box p {
      margin-top: 12px;
      font-size: 14px;
      color: #ccc;
    }

    .login-box a {
      color: #00eaff;
      text-decoration: none;
    }

    .error-message {
      background-color: #ff3c3c;
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 8px;
    }
  </style>
</head>
<body>
  <div class="background"></div>

  <header>
    <div class="logo">🎮 GameHub</div>
    <nav>
      <a href="index.php">Ігри</a>
    </nav>
  </header>

  <main>
    <div class="login-box">
      <h1>Увійти до акаунту</h1>

      <?php if ($error): ?>
        <div class="error-message"><?php echo $error; ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <input type="text" name="username" placeholder="Логін" required>
        <input type="password" name="password" placeholder="Пароль" required>
        <button type="submit">Увійти</button>
      </form>

      <p>Немає акаунту? <a href="register.php">Зареєструватися</a></p>
    </div>
  </main>

  <footer>
    <p>© 2025 GameHub | Telegram | Контакти</p>
  </footer>
</body>
</html>
