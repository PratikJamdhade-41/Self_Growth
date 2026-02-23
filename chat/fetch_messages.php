<?php
session_start();
require_once __DIR__ . "/../config/config.php";

$me = $_SESSION['user_id'];
$friend = (int)$_GET['id'];

$stmt = $conn->prepare("
SELECT * FROM messages
WHERE 
(sender_id=? AND receiver_id=?)
OR
(sender_id=? AND receiver_id=?)
ORDER BY created_at ASC
");
$stmt->bind_param("iiii",$me,$friend,$friend,$me);
$stmt->execute();
$result = $stmt->get_result();

while($m = $result->fetch_assoc()){

    $class = $m['sender_id'] == $me ? "me" : "friend";
    echo "<div class='message $class'>";
    echo htmlspecialchars($m['message']);
    echo "</div>";

    if($m['receiver_id'] == $me && $m['is_read'] == 0){
        $update = $conn->prepare("UPDATE messages SET is_read=1 WHERE id=?");
        $update->bind_param("i",$m['id']);
        $update->execute();
    }
}