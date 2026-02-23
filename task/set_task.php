<?php
session_start();
require_once "config.php";

$user_id = $_SESSION['user_id'];

$category = $_POST['category'];
$title = $_POST['task_title'];
$difficulty = $_POST['difficulty'];

if ($difficulty == "easy") {
    $xp = 20;
    $stat = 1;
} else {
    $xp = 50;
    $stat = 3;
}

$stmt = $conn->prepare("
INSERT INTO custom_tasks (user_id, category, task_title, xp_reward, stat_value)
VALUES (?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
task_title=VALUES(task_title),
xp_reward=VALUES(xp_reward),
stat_value=VALUES(stat_value)
");

$stmt->bind_param("issii", $user_id, $category, $title, $xp, $stat);
$stmt->execute();

header("Location: tasks.php");
exit();
?>