<?php
session_start();
include 'includes/db.php'; // Підключаємося до бази даних

ini_set('display_errors', 1); // Тимчасово увімкнено для налагодження
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$selected_game_id = isset($_GET['game_id']) ? (int)$_GET['game_id'] : 0; // Отримуємо ID гри з URL

// Отримання списку всіх ігор для фільтру
$games_list = [];
$queryGames = $conn->query("SELECT id, name FROM games ORDER BY name ASC");
if ($queryGames) {
    while ($row = $queryGames->fetch_assoc()) {
        $games_list[] = $row;
    }
}

// Запит для отримання даних для лідерборду
$leaderboard_query = "
    SELECT
        u.username,
        MAX(r.score) AS best_score,
        g.name AS game_name
    FROM
        results r
    JOIN
        users u ON r.user_id = u.id
    JOIN
        games g ON r.game_id = g.id
";

$params = [];
$types = "";

if ($selected_game_id > 0) {
    $leaderboard_query .= " WHERE r.game_id = ?";
    $params[] = $selected_game_id;
    $types .= "i";
}

$leaderboard_query .= "
    GROUP BY
        u.id, g.id
    ORDER BY
        best_score DESC, u.username ASC
    LIMIT 20"; // Показати топ-20 результатів

$stmt_leaderboard = $conn->prepare($leaderboard_query);

if ($params) {
    $stmt_leaderboard->bind_param($types, ...$params);
}

$stmt_leaderboard->execute();
$leaderboard_results = $stmt_leaderboard->get_result();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Рейтинг | GameHub</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron&display=swap" rel="stylesheet" />
    <style>
        .leaderboard-container {
            margin-top: 40px;
        }

        .leaderboard-container h1 {
            font-size: 36px;
            margin-bottom: 20px;
            text-align: center;
        }

        .filter-game {
            text-align: center;
            margin-bottom: 30px;
        }

        .filter-game select {
            padding: 10px 15px;
            border-radius: 8px;
            background-color: rgba(255,255,255,0.15);
            color: #eee;
            border: 1px solid rgba(255,255,255,0.2);
            font-size: 16px;
            appearance: none; /* Приховати стандартну стрілку */
            -webkit-appearance: none;
            -moz-appearance: none;
            cursor: pointer;
        }

        .filter-game select option {
            background-color: #1a1a2e; /* Фон для опцій */
            color: #eee;
        }

        .leaderboard-table {
            width: 90%;
            margin: 0 auto;
            border-collapse: collapse;
            background-color: rgba(255,255,255,0.1);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 15px rgba(0,0,0,0.6);
        }

        .leaderboard-table th, .leaderboard-table td {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.15);
            color: #eee;
            text-align: left;
        }

        .leaderboard-table th {
            background-color: rgba(0,0,0,0.5);
            text-transform: uppercase;
            font-size: 16px;
        }

        .leaderboard-table tr:last-child td {
            border-bottom: none;
        }

        .leaderboard-table tr:hover {
            background-color: rgba(255,255,255,0.08);
        }

        .leaderboard-table .rank {
            text-align: center;
            width: 50px;
        }

        /* Додаткові стилі для "no results" */
        .no-results {
            text-align: center;
            color: #eee;
            margin-top: 30px;
            font-size: 20px;
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
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="profile.php">Профіль</a>
                <a href="logout.php">Вийти</a>
            <?php else: ?>
                <a href="login.php">Увійти</a>
                <a href="register.php">Реєстрація</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="leaderboard-page">
        <div class="leaderboard-container">
            <h1>Рейтинг гравців</h1>

            <div class="filter-game">
                <form action="leaderboard.php" method="GET">
                    <label for="game_filter">Фільтр за грою:</label>
                    <select name="game_id" id="game_filter" onchange="this.form.submit()">
                        <option value="0">Усі ігри</option>
                        <?php foreach ($games_list as $game): ?>
                            <option value="<?php echo htmlspecialchars($game['id']); ?>"
                                <?php echo ($selected_game_id == $game['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($game['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <table class="leaderboard-table">
                <thead>
                    <tr>
                        <th class="rank">#</th>
                        <th>Користувач</th>
                        <th>Найкращий Рахунок</th>
                        <th>Гра</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $rank = 1;
                    if ($leaderboard_results->num_rows > 0):
                        while ($row = $leaderboard_results->fetch_assoc()): ?>
                        <tr>
                            <td class="rank"><?php echo $rank++; ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><strong><?php echo $row['best_score']; ?></strong></td>
                            <td><?php echo htmlspecialchars($row['game_name']); ?></td>
                        </tr>
                        <?php endwhile;
                    else: ?>
                        <tr>
                            <td colspan="4" class="no-results">Поки що немає результатів.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <footer>
        <p>© 2025 GameHub | Telegram | Контакти</p>
    </footer>
</div>
</body>
</html>

<?php
$conn->close(); // Закриваємо з'єднання з базою даних
?>