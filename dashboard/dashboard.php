<?php
session_start();
require_once __DIR__ . "/../config/config.php";
date_default_timezone_set('Asia/Kolkata');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* Update activity */
$update = $conn->prepare("UPDATE users SET last_active=NOW() WHERE id=?");
$update->bind_param("i", $user_id);
$update->execute();

/* Fetch user */
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

/* ================= DAILY PENALTY ================= */
$today = date("Y-m-d");
$penalty_alert = false;

if ($user['last_penalty_check'] != $today) {

    $stmt = $conn->prepare("
        SELECT COUNT(*) as total
        FROM task_history
        WHERE user_id=? AND DATE(completed_at)=?
    ");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $count_today = $stmt->get_result()->fetch_assoc()['total'];

    if ($count_today == 0) {
        $new_xp = max(0, $user['xp'] - 20);

        $update = $conn->prepare("
            UPDATE users SET xp=?, last_penalty_check=? WHERE id=?
        ");
        $update->bind_param("isi", $new_xp, $today, $user_id);
        $update->execute();

        $user['xp'] = $new_xp;
        $penalty_alert = true;
    } else {
        $update = $conn->prepare("
            UPDATE users SET last_penalty_check=? WHERE id=?
        ");
        $update->bind_param("si", $today, $user_id);
        $update->execute();
    }
}

/* XP */
$required_xp = $user['level'] * 100;
$xp_percent = ($required_xp > 0) ? ($user['xp'] / $required_xp) * 100 : 0;

/* Weekly */
$stmt = $conn->prepare("
    SELECT COUNT(*) as total
    FROM task_history
    WHERE user_id=? 
    AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$weekly_tasks = $stmt->get_result()->fetch_assoc()['total'];

/* Total Tasks */
$stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM task_history 
    WHERE user_id=?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_tasks = $stmt->get_result()->fetch_assoc()['total'];

/* Streak */
$completed_days = [];
$stmt = $conn->prepare("
    SELECT DISTINCT DATE(completed_at) as day
    FROM task_history
    WHERE user_id=?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res_days = $stmt->get_result();
while ($row = $res_days->fetch_assoc()) {
    $completed_days[] = $row['day'];
}

$streak = 0;
for ($i = 0; $i < 365; $i++) {
    $check_date = date("Y-m-d", strtotime("-$i days"));
    if (in_array($check_date, $completed_days)) {
        $streak++;
    } else {
        break;
    }
}

/* ================= ACHIEVEMENTS ================= */
function unlockAchievement($conn, $user_id, $key)
{
    $check = $conn->prepare("
        SELECT id FROM achievements 
        WHERE user_id=? AND achievement_key=?
    ");
    $check->bind_param("is", $user_id, $key);
    $check->execute();
    $check->store_result();

    if ($check->num_rows == 0) {
        $insert = $conn->prepare("
            INSERT INTO achievements (user_id, achievement_key)
            VALUES (?, ?)
        ");
        $insert->bind_param("is", $user_id, $key);
        $insert->execute();
    }
}

if ($streak >= 7) unlockAchievement($conn, $user_id, "7_day_streak");
if ($total_tasks >= 100) unlockAchievement($conn, $user_id, "100_tasks");
if ($user['level'] >= 10) unlockAchievement($conn, $user_id, "level_10");

/* Load achievements */
$stmt = $conn->prepare("SELECT achievement_key FROM achievements WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res_ach = $stmt->get_result();
$user_achievements = [];
while ($row = $res_ach->fetch_assoc()) {
    $user_achievements[] = $row['achievement_key'];
}

/* Heatmap */
$heatmap = [];
$stmt = $conn->prepare("
    SELECT DATE(completed_at) as day, COUNT(*) as total
    FROM task_history
    WHERE user_id=?
    AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
    GROUP BY DATE(completed_at)
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res_heat = $stmt->get_result();
while ($row = $res_heat->fetch_assoc()) {
    $heatmap[$row['day']] = $row['total'];
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1e1f4b, #12132e);
            color: white;
            padding: 30px;
        }

        /* NAVBAR */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 30px;
            background: rgba(31, 33, 74, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
            margin-bottom: 30px;
        }

        .nav-left {
            font-size: 20px;
            font-weight: 600;
        }

        .nav-right {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .nav-right a {
            text-decoration: none;
            color: #bbb;
            transition: 0.3s;
        }

        .nav-right a:hover {
            color: white;
        }

        .logout-btn {
            background: #e74a3b;
            padding: 6px 14px;
            border-radius: 20px;
            color: white !important;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        .card {
            background: #25275a;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
        }

        .progress {
            background: rgba(255, 255, 255, 0.1);
            height: 15px;
            border-radius: 20px;
        }

        .fill {
            background: linear-gradient(90deg, #7fb3ff, #4d94ff);
            height: 15px;
            border-radius: 20px;
            width: <?php echo $xp_percent; ?>%;
        }

        .alert {
            background: #ff4d4d;
            padding: 10px;
            border-radius: 10px;
            margin-top: 10px;
        }

        .heatmap {
            display: grid;
            grid-template-columns: repeat(15, 1fr);
            gap: 5px;
            margin-top: 15px;
        }

        .heat {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.08);
        }

        .level1 {
            background: #4d94ff;
        }

        .level2 {
            background: #1cc88a;
        }

        .level3 {
            background: #f6c23e;
        }

        .level4 {
            background: #e74a3b;
        }
    </style>
</head>

<body>

    <div class="navbar">
        <div class="nav-left">🚀 Self Growth</div>
        <div class="nav-right">
            <a href="tasks.php">Tasks</a>
            <a href="leaderboard.php?view=global">Leaderboard</a>
            <a href="profile.php?id=<?php echo $user_id; ?>">Profile</a>
            <a href="../auth/logout.php"
                onclick="return confirm('Are you sure you want to logout?')"
                class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="card">
        <h2>Welcome <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
        <p>Level <?php echo $user['level']; ?></p>
        <p>XP <?php echo $user['xp']; ?> / <?php echo $required_xp; ?></p>
        <div class="progress">
            <div class="fill"></div>
        </div>
        <p>🔥 Streak: <?php echo $streak; ?> days</p>
        <p>📅 This Week: <?php echo $weekly_tasks; ?></p>
        <?php if ($penalty_alert): ?>
            <div class="alert">⚠ No tasks today. 20 XP deducted.</div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>🏅 Achievements</h3>
        <?php if (in_array("7_day_streak", $user_achievements)) echo "<p>🔥 7 Day Streak Master</p>"; ?>
        <?php if (in_array("100_tasks", $user_achievements)) echo "<p>🎯 100 Tasks Completed</p>"; ?>
        <?php if (in_array("level_10", $user_achievements)) echo "<p>👑 Level 10 Unlocked</p>"; ?>
        <?php if (empty($user_achievements)) echo "<p>No achievements yet.</p>"; ?>
    </div>

    <div class="card">
        <h3>📊 Consistency Heatmap (Last 90 Days)</h3>
        <div class="heatmap">
            <?php
            for ($i = 89; $i >= 0; $i--) {
                $date = date("Y-m-d", strtotime("-$i days"));
                $count = $heatmap[$date] ?? 0;
                $class = "heat";
                if ($count >= 1) $class .= " level1";
                if ($count >= 3) $class .= " level2";
                if ($count >= 5) $class .= " level3";
                if ($count >= 8) $class .= " level4";
                echo "<div class='$class' title='$date : $count tasks'></div>";
            }
            ?>
        </div>
    </div>

    <div class="card">
        <h3>📊 Stats Overview</h3>
        <canvas id="statsChart"></canvas>
    </div>

    <script>
        const ctx = document.getElementById('statsChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Strength', 'Intelligence', 'Discipline', 'Focus'],
                datasets: [{
                    data: [
                        <?php echo $user['strength']; ?>,
                        <?php echo $user['intelligence']; ?>,
                        <?php echo $user['discipline']; ?>,
                        <?php echo $user['focus']; ?>
                    ],
                    backgroundColor: ['#7fb3ff', '#4d94ff', '#36b9cc', '#1cc88a']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>

</body>

</html>