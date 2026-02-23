<?php
session_start();
require_once __DIR__ . "/../config/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* ===============================
   FETCH USER DATA
=================================*/
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$level = $user['level'];

/* ===============================
   DEFAULT TASKS
=================================*/
$tasks = [
    "study" => ["title" => "📚 Study Session", "reward" => 30, "stat" => 2, "stat_name" => "Intelligence"],
    "gym" => ["title" => "🏋️ Gym Workout", "reward" => 30, "stat" => 2, "stat_name" => "Strength"],
    "read" => ["title" => "📖 Reading Time", "reward" => 15, "stat" => 1, "stat_name" => "Focus"],
    "discipline" => ["title" => "🚫 No Social Media", "reward" => 20, "stat" => 2, "stat_name" => "Discipline"]
];

/* ===============================
   LOAD CUSTOM TASKS
=================================*/
$stmt = $conn->prepare("SELECT * FROM custom_tasks WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $tasks[$row['category']] = [
        "title" => "⭐ " . $row['task_title'],
        "reward" => $row['xp_reward'],
        "stat" => $row['stat_value'],
        "stat_name" => ucfirst($row['category'])
    ];
}

/* ===============================
   CHECK COMPLETED TODAY
=================================*/
$completed_today = [];
$stmt = $conn->prepare("
SELECT task_name FROM task_history
WHERE user_id=? AND DATE(completed_at)=CURDATE()
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $completed_today[] = $row['task_name'];
}

/* ===============================
   AUTO RECOMMEND
=================================*/
$userStats = [
    "study" => $user['intelligence'],
    "gym" => $user['strength'],
    "discipline" => $user['discipline'],
    "read" => $user['focus']
];

$weakest_category = array_keys($userStats, min($userStats))[0];
?>

<!DOCTYPE html>
<html>

<head>
    <title>Tasks</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1e1f4b, #12132e);
            color: white;
            min-height: 100vh;
            padding: 40px 20px;
        }

        /* Main Container */
        .container {
            max-width: 1000px;
            margin: auto;
            background: #1f214a;
            border-radius: 25px;
            padding: 35px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.5);
        }

        /* Title */
        h2 {
            margin-bottom: 25px;
        }

        /* Card */
        .card {
            background: #25275a;
            padding: 20px;
            border-radius: 18px;
            margin-bottom: 20px;
            transition: 0.3s;
        }

        .card:hover {
            background: rgba(127, 179, 255, 0.12);
            transform: translateY(-3px);
        }

        /* Recommended */
        .recommended {
            border-left: 5px solid orange;
            background: rgba(255, 165, 0, 0.1);
        }

        /* Premium */
        .premium {
            border-left: 5px solid gold;
        }

        .locked {
            opacity: 0.5;
        }

        /* Buttons */
        .btn {
            padding: 8px 14px;
            border-radius: 20px;
            border: none;
            cursor: pointer;
            font-size: 13px;
            margin-top: 10px;
            background: linear-gradient(90deg, #7fb3ff, #4d94ff);
            color: white;
            transition: 0.3s;
        }

        .btn:hover {
            transform: scale(1.05);
        }

        .btn:disabled {
            background: gray;
        }

        /* Form Elements */
        input,
        select {
            padding: 6px 10px;
            border-radius: 10px;
            border: none;
            margin-top: 8px;
            margin-right: 5px;
        }

        hr {
            border: 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin: 15px 0;
        }

        /* Back */
        .back {
            display: inline-block;
            margin-top: 20px;
            color: #7fb3ff;
            text-decoration: none;
        }

        @media(max-width:768px) {
            .container {
                padding: 20px;
            }
        }
    </style>
</head>

<body>

    <div class="container">

        <h2>📋 Daily Missions</h2>

        <!-- Recommended -->
        <div class="card recommended">
            <h3>🧠 Recommended Upgrade</h3>
            <p>Improve your weakest stat: <strong><?php echo ucfirst($weakest_category); ?></strong></p>
        </div>

        <?php foreach ($tasks as $key => $task):
            $is_completed = in_array($key, $completed_today);
        ?>

            <div class="card">

                <h3><?php echo $task['title']; ?></h3>
                <p>+<?php echo $task['reward']; ?> XP | +<?php echo $task['stat']; ?> <?php echo $task['stat_name']; ?></p>

                <?php if ($is_completed): ?>
                    <button class="btn" disabled>✔ Mission Completed</button>
                <?php else: ?>
                    <form action="complete_task.php" method="POST">
                        <input type="hidden" name="task" value="<?php echo $key; ?>">
                        <button class="btn">Complete Mission</button>
                    </form>
                <?php endif; ?>

                <hr>

                <form action="set_task.php" method="POST">
                    <input type="hidden" name="category" value="<?php echo $key; ?>">
                    <input type="text" name="task_title" placeholder="Custom Mission" required>
                    <select name="difficulty">
                        <option value="easy">Easy</option>
                        <option value="hard">Hard</option>
                    </select>
                    <button class="btn">Replace Mission</button>
                </form>

            </div>

        <?php endforeach; ?>

        <!-- Premium -->
        <?php if ($level >= 10): ?>
            <div class="card premium">
                <h3>👑 Elite Challenge</h3>
                <p>3 Hour Deep Work</p>
                <p>+100 XP | +5 Intelligence</p>
                <form action="complete_task.php" method="POST">
                    <input type="hidden" name="task" value="premium">
                    <button class="btn">Complete Elite</button>
                </form>
            </div>
        <?php else: ?>
            <div class="card premium locked">
                <h3>🔒 Elite Challenge</h3>
                <p>Unlocks at Level 10</p>
            </div>
        <?php endif; ?>

        <a class="back" href="dashboard.php">⬅ Back to Dashboard</a>

    </div>

</body>

</html>