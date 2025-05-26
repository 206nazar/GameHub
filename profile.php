<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$queryUser = $conn->prepare("SELECT username FROM users WHERE id = ?");
$queryUser->bind_param("i", $user_id);
$queryUser->execute();
$queryUser->bind_result($username);
$queryUser->fetch();
$queryUser->close();

$queryStats = $conn->prepare("
    SELECT COUNT(*) AS total_games, MAX(score) AS best_score,
    COUNT(CASE WHEN score >= 100 THEN 1 END) AS wins
    FROM results WHERE user_id = ?");
$queryStats->bind_param("i", $user_id);
$queryStats->execute();
$queryStats->bind_result($total_games, $best_score, $wins);
$queryStats->fetch();
$queryStats->close();

$queryHistory = $conn->prepare("
    SELECT g.name AS game_name, r.score, r.played_at
    FROM results r
    JOIN games g ON r.game_id = g.id
    WHERE r.user_id = ?
    ORDER BY r.played_at DESC LIMIT 10");
$queryHistory->bind_param("i", $user_id);
$queryHistory->execute();
$history = $queryHistory->get_result();

$allAchievements = [
  "general" => [
    "🎯 Перший крок" => "Зіграно першу гру",
    "🏆 Перемога!" => "Отримано 100+ балів",
    "🧠 Досвідчений" => "Зіграно 10 ігор",
    "🎮 Полювання" => "Зіграно 50 ігор",
    "💎 Профі" => "Отримано 300+ балів у будь-якій грі",
    "🧊 Усі ігри" => "Зіграно всі доступні ігри",
    "📈 Безперервність" => "Грали 3 дні підряд",
    "🔥 Серія" => "3 гри поспіль з результатом 100+"
  ],
  "snake" => [
    "🟢 Старт змійки" => "Зіграти першу гру у Змійку",
    "🟡 Перша 50" => "Отримати 50 балів у Змійці",
    "🟠 Половина сотні" => "Досягти 100 балів у Змійці",
    "🔵 Майстер змійки" => "Отримати 150 балів у Змійці",
    "🟣 Зміїний бог" => "Отримати 200 балів у Змійці",
    "🔁 5 ігор у змійку" => "Зіграти 5 разів у Змійку"
  ],
  "tictactoe" => [
    "🟢 Перша перемога" => "Перемога в Хрестиках вперше",
    "❌ Перша поразка" => "Програш у Хрестиках",
    "🔁 Нічия" => "Гру завершено внічию",
    "🎯 Стратег" => "3 перемоги у Хрестиках",
    "🧠 Переможець бота" => "Перемогти ШІ",
    "🎮 10 ігор" => "Зіграти 10 ігор у Хрестиках",
    "🏆 Чемпіон хрестиків" => "10 перемог у Хрестиках",
    "🔁 Серія перемог" => "3 перемоги підряд",
    "📆 3 дні поспіль" => "Грати 3 дні поспіль у Хрестиках"
  ],
  "2048" => [
    "🟦 Старт 2048" => "Зіграти одну гру в 2048",
    "🔢 Плитка 512" => "Отримати плитку 512+",
    "💠 Плитка 1024" => "Отримати плитку 1024+",
    "🎯 Досягнуто 2048" => "Плитка 2048+",
    "💎 Суперрахунок" => "Набрати 3000+ очок",
    "🔁 5 ігор у 2048" => "Зіграти 5 разів у 2048"
  ]
];

$earned = [];
$queryAch = $conn->prepare("SELECT title, earned_at FROM achievements WHERE user_id = ?");
$queryAch->bind_param("i", $user_id);
$queryAch->execute();
$res = $queryAch->get_result();
while ($row = $res->fetch_assoc()) {
    $earned[$row['title']] = $row['earned_at'];
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8">
  <title>Профіль | GameHub</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron&display=swap" rel="stylesheet" />
  <style>
    .profile-header h1 {
      font-size: 36px;
      margin-bottom: 10px;
    }
    .profile-stats {
      display: flex;
      justify-content: center;
      gap: 30px;
      margin-top: 30px;
      flex-wrap: wrap;
    }
    .stat-box {
      background: rgba(255,255,255,0.08);
      padding: 20px;
      border-radius: 12px;
      min-width: 160px;
      font-size: 18px;
      box-shadow: 0 0 10px rgba(0,0,0,0.5);
    }
    .profile-columns {
      display: flex;
      justify-content: center;
      align-items: flex-start;
      gap: 40px;
      flex-wrap: wrap;
      margin-top: 50px;
      padding: 0 30px;
      max-width: 1300px;
      margin-left: auto;
      margin-right: auto;
    }
    .profile-columns .history, .profile-columns .achievements {
      flex: 1 1 600px;
      min-width: 300px;
    }
    .history table {
      width: 100%;
      border-collapse: collapse;
      background-color: rgba(255,255,255,0.1);
      border-radius: 10px;
      overflow: hidden;
    }
    .history th, .history td {
      padding: 12px 18px;
      border-bottom: 1px solid rgba(255,255,255,0.1);
      color: #eee;
    }
    .history th {
      background-color: rgba(0,0,0,0.5);
    }
    .history tr:hover {
      background-color: rgba(255,255,255,0.05);
    }
    .achievements h2 {
      font-size: 28px;
      margin-bottom: 20px;
      color: #00eaff;
      text-align: center;
    }
    .achievements-grid {
      display: flex;
      flex-direction: column;
      gap: 16px;
      max-height: 600px;
      overflow-y: auto;
      padding-right: 10px;
    }
    .achievements-grid::-webkit-scrollbar {
      width: 6px;
    }
    .achievements-grid::-webkit-scrollbar-thumb {
      background: #00eaff;
      border-radius: 3px;
    }
    .achievement-card {
      background: rgba(255, 255, 255, 0.07);
      border-radius: 12px;
      padding: 20px;
      color: #eee;
      box-shadow: 0 0 10px rgba(0,0,0,0.3);
      opacity: 0.4;
      filter: grayscale(100%);
    }
    .achievement-card.unlocked {
      opacity: 1;
      filter: none;
      box-shadow: 0 0 16px rgba(0,255,255,0.4);
      background: rgba(0, 255, 255, 0.1);
    }
    .achievement-card .title {
      font-size: 18px;
      font-weight: bold;
      margin-bottom: 5px;
    }
    .achievement-card .desc {
      font-size: 14px;
      margin-bottom: 8px;
      color: #ccc;
    }
    .achievement-card .date {
      font-size: 12px;
      color: #aaa;
    }
    @media (max-width: 768px) {
      .profile-columns {
        flex-direction: column;
        align-items: center;
      }
    }
  </style>
</head>
<body>
<div class="background"></div>
<div class="wrapper">
  <header>
    <div class="logo">🎮 GameHub</div>
    <nav>
      <a href="index.php">Ігри</a>
      <a href="leaderboard.php">Рейтинг</a>
      <a href="logout.php">Вийти</a>
    </nav>
  </header>

  <main class="profile-page">
    <section class="profile-header">
      <h1>Привіт, <?php echo htmlspecialchars($username); ?>!</h1>
      <p>Твоя статистика гри</p>
    </section>

    <section class="profile-stats">
      <div class="stat-box">Ігор зіграно: <strong><?php echo $total_games ?? 0; ?></strong></div>
      <div class="stat-box">Найкращий результат: <strong><?php echo $best_score ?? 0; ?></strong></div>
      <div class="stat-box">Перемог (100+): <strong><?php echo $wins ?? 0; ?></strong></div>
    </section>

    <div class="profile-columns">
      <section class="history">
        <h2>Останні ігри</h2>
        <table>
          <tr><th>Гра</th><th>Рахунок</th><th>Дата</th></tr>
          <?php while ($row = $history->fetch_assoc()): ?>
          <tr>
            <td><?php echo htmlspecialchars($row['game_name']); ?></td>
            <td><?php echo $row['score']; ?></td>
            <td><?php echo date("d.m.Y H:i", strtotime($row['played_at'])); ?></td>
          </tr>
          <?php endwhile; ?>
        </table>
      </section>

      <section class="achievements">
        <h2>🏅 Досягнення</h2>
        <div class="achievements-grid">
          <?php foreach ($allAchievements as $category => $group): ?>
            <?php
              $label = match ($category) {
                  'snake' => '🐍 Змійка',
                  'tictactoe' => '❌ Хрестики-нулики',
                  '2048' => '🧮 2048',
                  'general' => '🌟 Загальні',
                  default => '🎮 Інше'
              };
            ?>
            <h3 style="color:#0ff; text-align:center; margin-top:20px;"><?php echo $label; ?></h3>
            <?php foreach ($group as $title => $desc): 
              $unlocked = isset($earned[$title]);
            ?>
              <div class="achievement-card <?php echo $unlocked ? 'unlocked' : 'locked'; ?>">
                <div class="title"><?php echo $title; ?></div>
                <div class="desc"><?php echo $desc; ?></div>
                <div class="date">
                  <?php echo $unlocked ? 'Отримано: ' . date("d.m.Y", strtotime($earned[$title])) : '🔒 Не отримано'; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </div>
      </section>
    </div>
  </main>

  <footer>
    <p>© 2025 GameHub | Telegram | Контакти</p>
  </footer>
</div>
</body>
</html>
