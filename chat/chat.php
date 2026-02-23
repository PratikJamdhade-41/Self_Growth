<?php
session_start();
require_once __DIR__ . "/../config/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$me = $_SESSION['user_id'];
$friend = (int)($_GET['id'] ?? 0);

if (!$friend || $friend == $me) {
    header("Location: dashboard.php");
    exit();
}

/* Update activity */
$update = $conn->prepare("UPDATE users SET last_active=NOW() WHERE id=?");
$update->bind_param("i", $me);
$update->execute();

/* Check friendship */
$check = $conn->prepare("
SELECT id FROM friends
WHERE 
(
    (sender_id=? AND receiver_id=?)
    OR
    (sender_id=? AND receiver_id=?)
)
AND status='accepted'
");
$check->bind_param("iiii", $me, $friend, $friend, $me);
$check->execute();
$check->store_result();

if ($check->num_rows == 0) {
    echo "You can only chat with friends.";
    exit();
}

/* Send message */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $msg = trim($_POST['message']);
    if (!empty($msg)) {
        $stmt = $conn->prepare("
        INSERT INTO messages (sender_id, receiver_id, message)
        VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iis", $me, $friend, $msg);
        $stmt->execute();
    }
    header("Location: chat.php?id=".$friend);
    exit();
}

/* Friend info */
$stmt = $conn->prepare("SELECT username, last_active FROM users WHERE id=?");
$stmt->bind_param("i", $friend);
$stmt->execute();
$friend_data = $stmt->get_result()->fetch_assoc();

$is_online = false;
if ($friend_data['last_active']) {
    if (time() - strtotime($friend_data['last_active']) < 30) {
        $is_online = true;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Chat</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
*{margin:0;padding:0;box-sizing:border-box;}

body{
    font-family:'Segoe UI',sans-serif;
    background:linear-gradient(135deg,#1e1f4b,#12132e);
    padding:40px 20px;
    color:white;
}

.container{
    max-width:650px;
    margin:auto;
    background:#1f214a;
    padding:30px;
    border-radius:25px;
    box-shadow:0 30px 80px rgba(0,0,0,0.5);
}

.chat-box{
    background:#25275a;
    padding:15px;
    border-radius:15px;
    height:400px;
    overflow-y:auto;
    margin-bottom:15px;
}

.message{
    margin-bottom:10px;
    padding:8px 12px;
    border-radius:15px;
    display:inline-block;
    max-width:75%;
    clear:both;
}

.me{
    text-align:right;
    background:linear-gradient(90deg,#7fb3ff,#4d94ff);
    color:white;
    float:right;
}

.friend{
    text-align:left;
    background:rgba(255,255,255,0.08);
    color:white;
    float:left;
}

input{
    width:70%;
    padding:10px;
    border-radius:20px;
    border:none;
    outline:none;
    background:rgba(255,255,255,0.1);
    color:white;
}

button{
    padding:10px 16px;
    background:linear-gradient(90deg,#7fb3ff,#4d94ff);
    color:white;
    border:none;
    border-radius:20px;
    cursor:pointer;
    transition:0.3s;
}

button:hover{
    transform:scale(1.05);
}

.online{color:#4CAF50;}
.offline{color:#888;}

a{
    color:#7fb3ff;
    text-decoration:none;
}
</style>

<script>
function loadMessages(){
    fetch("fetch_messages.php?id=<?php echo $friend; ?>")
    .then(response => response.text())
    .then(data => {
        document.getElementById("chatBox").innerHTML = data;
        document.getElementById("chatBox").scrollTop = 
            document.getElementById("chatBox").scrollHeight;
    });
}
setInterval(loadMessages, 3000);
</script>

</head>
<body onload="loadMessages()">

<div class="container">

<h2>
💬 Chat with <?php echo htmlspecialchars($friend_data['username']); ?>
<?php if($is_online): ?>
<span class="online">● Online</span>
<?php else: ?>
<span class="offline">● Offline</span>
<?php endif; ?>
</h2>

<div class="chat-box" id="chatBox"></div>

<form method="POST">
<input type="text" name="message" required>
<button>Send</button>
</form>

<br>
<a href="../dashboard/dashboard.php">⬅ Back</a>

</div>

</body>
</html>