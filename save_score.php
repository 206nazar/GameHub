<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo "Не авторизовано";
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['game_code']) || !isset($data['score'])) {
    http_response_code(400);
    echo "Некоректні дані";
    exit();
}

$game_code = $data['game_code'];
$score = intval($data['score']);
$user_id = $_SESSION['user_id'];

// Отримати ID гри
$stmt = $conn->prepare("SELECT id FROM games WHERE code = ?");
$stmt->bind_param("s", $game_code);
$stmt->execute();
$stmt->bind_result($game_id);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo "Гра не знайдена";
    exit();
}
$stmt->close();

// Додати результат
$insert = $conn->prepare("INSERT INTO results (user_id, game_id, score) VALUES (?, ?, ?)");
$insert->bind_param("iii", $user_id, $game_id, $score);
$insert->execute();

// Перевірити досягнення
checkAchievements($conn, $user_id, $game_id, $score);

echo "OK";

// Функція: Видати досягнення
function grant($conn, $user_id, $title, $desc, $condition) {
    if (!$condition) return;
    $stmt = $conn->prepare("SELECT id FROM achievements WHERE user_id = ? AND title = ?");
    $stmt->bind_param("is", $user_id, $title);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        $insert = $conn->prepare("INSERT INTO achievements (user_id, title, description) VALUES (?, ?, ?)");
        $insert->bind_param("iss", $user_id, $title, $desc);
        $insert->execute();
    }
}

// Функція: Отримати ID гри по коду
function getGameId($conn, $code) {
    $stmt = $conn->prepare("SELECT id FROM games WHERE code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $stmt->bind_result($id);
    $stmt->fetch();
    $stmt->close();
    return $id;
}

// Основна логіка досягнень
function checkAchievements($conn, $user_id, $game_id, $score) {
    $totalGames = $conn->query("SELECT COUNT(*) AS total FROM results WHERE user_id = $user_id")->fetch_assoc()['total'];

    // 🔸 Загальні
    grant($conn, $user_id, "🎯 Перший крок", "Зіграно першу гру", $totalGames == 1);
    grant($conn, $user_id, "🧠 Досвідчений", "Зіграно 10 ігор", $totalGames >= 10);
    grant($conn, $user_id, "🎮 Полювання", "Зіграно 50 ігор", $totalGames >= 50);
    grant($conn, $user_id, "🏆 Перемога!", "Отримано 100+ балів", $score >= 100);
    grant($conn, $user_id, "💎 Профі", "Отримано 300+ балів у будь-якій грі", $score >= 300);

    // 🔸 Усі ігри
    $played = $conn->query("SELECT COUNT(DISTINCT game_id) AS count FROM results WHERE user_id = $user_id")->fetch_assoc()['count'];
    $total = $conn->query("SELECT COUNT(*) AS count FROM games")->fetch_assoc()['count'];
    grant($conn, $user_id, "🧊 Усі ігри", "Зіграно всі доступні ігри", $played == $total);

    // 🔸 Безперервність
    $dates = $conn->query("SELECT DISTINCT DATE(played_at) AS d FROM results WHERE user_id = $user_id ORDER BY d DESC LIMIT 3");
    $days = [];
    while ($d = $dates->fetch_assoc()) $days[] = $d['d'];
    if (count($days) === 3) {
        $ts = array_map(fn($d) => strtotime($d), $days);
        if ($ts[0] - $ts[1] === 86400 && $ts[1] - $ts[2] === 86400) {
            grant($conn, $user_id, "📈 Безперервність", "Грали 3 дні підряд", true);
        }
    }

    // 🔸 Змійка
    $snakeId = getGameId($conn, 'snake');
    if ($game_id === $snakeId) {
        $snakeGames = $conn->query("SELECT COUNT(*) AS total FROM results WHERE user_id = $user_id AND game_id = $snakeId")->fetch_assoc()['total'];
        grant($conn, $user_id, "🟢 Старт змійки", "Зіграти першу гру у Змійку", $snakeGames >= 1);
        grant($conn, $user_id, "🟡 Перша 50", "Отримати 50 балів у Змійці", $score >= 50);
        grant($conn, $user_id, "🟠 Половина сотні", "Досягти 100 балів у Змійці", $score >= 100);
        grant($conn, $user_id, "🔵 Майстер змійки", "Отримати 150 балів у Змійці", $score >= 150);
        grant($conn, $user_id, "🟣 Зміїний бог", "Отримати 200 балів у Змійці", $score >= 200);
        grant($conn, $user_id, "🔁 5 ігор у змійку", "Зіграти 5 разів у Змійку", $snakeGames >= 5);
    }

    // 🔸 Хрестики-нулики
    $tttId = getGameId($conn, 'tictactoe');
    if ($game_id === $tttId) {
        $wins = $conn->query("SELECT COUNT(*) FROM results WHERE user_id = $user_id AND game_id = $tttId AND score = 1")->fetch_row()[0];
        $draws = $conn->query("SELECT COUNT(*) FROM results WHERE user_id = $user_id AND game_id = $tttId AND score = 0")->fetch_row()[0];
        $losses = $conn->query("SELECT COUNT(*) FROM results WHERE user_id = $user_id AND game_id = $tttId AND score = -1")->fetch_row()[0];
        $tttGames = $conn->query("SELECT COUNT(*) FROM results WHERE user_id = $user_id AND game_id = $tttId")->fetch_row()[0];

        grant($conn, $user_id, "🟢 Перша перемога", "Перемога в Хрестиках вперше", $wins >= 1);
        grant($conn, $user_id, "❌ Перша поразка", "Програш у Хрестиках", $losses >= 1);
        grant($conn, $user_id, "🔁 Нічия", "Гру завершено внічию", $draws >= 1);
        grant($conn, $user_id, "🎯 Стратег", "3 перемоги у Хрестиках", $wins >= 3);
        grant($conn, $user_id, "🧠 Переможець бота", "Перемогти ШІ", $score === 1);
        grant($conn, $user_id, "🎮 10 ігор", "Зіграти 10 ігор у Хрестиках", $tttGames >= 10);
        grant($conn, $user_id, "🏆 Чемпіон хрестиків", "10 перемог у Хрестиках", $wins >= 10);
    // 🔸 2048
    $game2048Id = getGameId($conn, '2048');
    if ($game_id === $game2048Id) {
        $g2048Total = $conn->query("SELECT COUNT(*) FROM results WHERE user_id = $user_id AND game_id = $game2048Id")->fetch_row()[0];

        grant($conn, $user_id, "🟦 Старт 2048", "Зіграти одну гру в 2048", $g2048Total >= 1);
        grant($conn, $user_id, "🔁 5 ігор у 2048", "Зіграти 5 разів у 2048", $g2048Total >= 5);
        grant($conn, $user_id, "🔢 Плитка 512", "Отримати плитку 512+", $score >= 512);
        grant($conn, $user_id, "💠 Плитка 1024", "Отримати плитку 1024+", $score >= 1024);
        grant($conn, $user_id, "🎯 Досягнуто 2048", "Плитка 2048+", $score >= 2048);
        grant($conn, $user_id, "💎 Суперрахунок", "Набрати 3000+ очок", $score >= 3000);
    }


        // Перевірка серії
        $last3 = $conn->query("SELECT score FROM results WHERE user_id = $user_id AND game_id = $tttId ORDER BY played_at DESC LIMIT 3");
        $combo = 0;
        while ($r = $last3->fetch_assoc()) {
            if ($r['score'] == 1) $combo++; else break;
        }
        grant($conn, $user_id, "🔁 Серія перемог", "3 перемоги підряд", $combo == 3);

        // Перевірка 3 днів поспіль у TTT
        $dates = $conn->query("SELECT DISTINCT DATE(played_at) as d FROM results WHERE user_id = $user_id AND game_id = $tttId ORDER BY d DESC LIMIT 3");
        $days = [];
        while ($d = $dates->fetch_assoc()) $days[] = $d['d'];
        if (count($days) === 3) {
            $ts = array_map(fn($d) => strtotime($d), $days);
            if ($ts[0] - $ts[1] === 86400 && $ts[1] - $ts[2] === 86400) {
                grant($conn, $user_id, "📆 3 дні поспіль", "Грати 3 дні поспіль у Хрестиках", true);
            }
        }
    }

    // 🔸 Загальна серія перемог у будь-якій грі
    $check = $conn->query("SELECT score FROM results WHERE user_id = $user_id ORDER BY played_at DESC LIMIT 3");
    $combo = 0;
    while ($r = $check->fetch_assoc()) {
        if ($r['score'] >= 100 || $r['score'] === 1) $combo++;
        else break;
    }
    grant($conn, $user_id, "🔥 Серія", "3 гри поспіль з результатом 100+", $combo == 3);
}
