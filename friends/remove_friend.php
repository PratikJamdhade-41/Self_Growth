<?php
session_start();
require_once __DIR__ . "/../config/config.php";

if (!isset($_SESSION['user_id'])) {
    exit();
}

$friend_id = $_POST['friend_id'];

$stmt = $conn->prepare("DELETE FROM friends WHERE id=?");
$stmt->bind_param("i", $friend_id);
$stmt->execute();

header("Location: dashboard.php");
exit();