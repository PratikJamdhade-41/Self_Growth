<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT streak, restore_key FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$streak = $user['streak'];
$restore_key = $user['restore_key'];

if ($restore_key > 0) {

    $restore_key--;
    $streak++; // restore previous streak continuation

    $update = $conn->prepare("
        UPDATE users 
        SET streak=?, restore_key=?, missed_day=0 
        WHERE id=?
    ");
    $update->bind_param("iii", $streak, $restore_key, $user_id);
    $update->execute();
}

header("Location: dashboard.php");
exit();
?>