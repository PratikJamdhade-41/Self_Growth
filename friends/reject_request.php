<?php
session_start();
require_once __DIR__ . "/../config/config.php";

if (!isset($_SESSION['user_id']) || !isset($_POST['request_id'])) {
    header("Location: friend_requests.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$request_id = (int)$_POST['request_id'];

/* Delete only if it belongs to logged-in user */
$stmt = $conn->prepare("
DELETE FROM friends
WHERE id=? AND receiver_id=? AND status='pending'
");
$stmt->bind_param("ii", $request_id, $user_id);
$stmt->execute();

header("Location: friend_requests.php");
exit();