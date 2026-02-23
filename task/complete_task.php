<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$task = $_POST['task'];

/* ===============================
   FETCH USER DATA
=================================*/
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$level = $user['level'];
$xp = $user['xp'];
$strength = $user['strength'];
$intelligence = $user['intelligence'];
$discipline = $user['discipline'];
$focus = $user['focus'];
$streak = $user['streak'];
$last_task_date = $user['last_task_date'];
$restore_key = $user['restore_key'];

/* ===============================
   PREVENT DOUBLE COMPLETION TODAY
=================================*/
$check = $conn->prepare("
    SELECT id FROM task_history 
    WHERE user_id = ? 
    AND task_name = ? 
    AND DATE(completed_at) = CURDATE()
");
$check->bind_param("is", $user_id, $task);
$check->execute();
$result_check = $check->get_result();

if ($result_check->num_rows > 0) {
    echo "Task already completed today.";
    echo "<br><a href='tasks.php'>Go Back</a>";
    exit();
}

/* ===============================
   TASK REWARDS
=================================*/
$reward = 0;

if ($task == "study") {
    $xp += 30;
    $intelligence += 2;
    $reward = 30;
}
elseif ($task == "gym") {
    $xp += 30;
    $strength += 2;
    $reward = 30;
}
elseif ($task == "read") {
    $xp += 15;
    $focus += 1;
    $reward = 15;
}
elseif ($task == "discipline") {
    $xp += 20;
    $discipline += 2;
    $reward = 20;
}

/* ===============================
   LEVEL SYSTEM
=================================*/
$required_xp = $level * 100;

if ($xp >= $required_xp) {
    $level++;
    $xp = 0;
    $discipline += 1; // bonus on level up
}

/* ===============================
   STREAK SYSTEM
=================================*/
$today = date("Y-m-d");
$yesterday = date("Y-m-d", strtotime("-1 day"));

if ($last_task_date == $yesterday) {
    $streak++;
}
elseif ($last_task_date == $today) {
    // already counted
}
else {
    if (!empty($last_task_date)) {
        if ($restore_key > 0) {
            $restore_key--;
            $streak++;
        } else {
            $streak = 1;
        }
    } else {
        $streak = 1;
    }
}

/* ===============================
   10 DAY BONUS
=================================*/
if ($streak >= 10) {
    $xp += 100;          // Bonus XP
    $restore_key += 1;   // Give restore key
    $streak = 0;         // Reset streak
}

/* ===============================
   UPDATE DATABASE
=================================*/
$update = $conn->prepare("
UPDATE users 
SET level=?, xp=?, strength=?, intelligence=?, discipline=?, focus=?, 
    streak=?, last_task_date=?, restore_key=? 
WHERE id=?
");

$update->bind_param(
    "iiiiiiisii",
    $level,
    $xp,
    $strength,
    $intelligence,
    $discipline,
    $focus,
    $streak,
    $today,
    $restore_key,
    $user_id
);

$update->execute();

/* ===============================
   SAVE TASK HISTORY
=================================*/
$history = $conn->prepare("
INSERT INTO task_history (user_id, task_name, xp_reward) 
VALUES (?, ?, ?)
");

$history->bind_param("isi", $user_id, $task, $reward);
$history->execute();

header("Location: dashboard.php");
exit();
?>