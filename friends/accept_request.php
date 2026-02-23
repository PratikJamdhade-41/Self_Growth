<?php
session_start();
require_once __DIR__ . "/../config/config.php";

/* Check login */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/* Check request ID */
if (!isset($_POST['request_id'])) {
    header("Location: friend_requests.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$request_id = (int) $_POST['request_id'];

/* Accept request ONLY if:
   - It belongs to this user
   - Status is pending
*/
$stmt = $conn->prepare("
UPDATE friends 
SET status='accepted' 
WHERE id=? 
AND receiver_id=? 
AND status='pending'
");

$stmt->bind_param("ii", $request_id, $user_id);
$stmt->execute();

header("Location: friend_requests.php");
exit();