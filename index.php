<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>GameHub</title>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="background"></div>
  <div class="wrapper">
    <header>
      <div class="logo">🎮 GameHub</div>
      <nav>
        <a href="index.php">Ігри</a>
        <a href="leaderboard.php">Рейтинг</a>
        <a href="profile.php">Профіль</a>
        <a href="logout.php">Вийти</a>
      </nav>
    </header>

    <main>
      <section class="welcome">
        <h1>Ласкаво просимо до GameHub!</h1>
        <p>Обери гру та почни змагання!</p>
      </section>

      <section class="games">
        <div class="game-card">
          <h3>Змійка</h3>
          <p>Класична аркада</p>
          <button onclick="location.href='snake.html'">Грати</button>
        </div>

        <div class="game-card">
          <h3>Хрестики-нулики</h3>
          <p>На двох або з ботом</p>
          <button onclick="location.href='tic-tac-toe.html'">Грати</button>
        </div>

        <!-- ✅ НОВА ГРА: 2048 -->
        <div class="game-card">
          <h3>2048</h3>
          <p>Об’єднай плитки та досягни числа 2048!</p>
          <button onclick="location.href='2048.html'">Грати</button>
        </div>
      </section>
    </main>

    <footer>
      <p>© 2025 GameHub | Telegram | Контакти</p>
    </footer>
  </div>
</body>
</html>
