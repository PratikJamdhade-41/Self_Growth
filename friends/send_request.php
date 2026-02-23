<?php
session_start();
require_once __DIR__ . "/../config/config.php";

$sender = $_SESSION['user_id'];
$receiver = $_POST['receiver_id'];

$stmt = $conn->prepare("
INSERT INTO friends (sender_id, receiver_id)
VALUES (?, ?)
");

$stmt->bind_param("ii", $sender, $receiver);
$stmt->execute();

header("Location: find_users.php");
exit();
?>