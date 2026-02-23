<?php
session_start();
require_once __DIR__ . "/../config/config.php";

if (!isset($_SESSION['user_id'])) {
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['query'])) {

    $search = "%" . $_GET['query'] . "%";

    $stmt = $conn->prepare("
        SELECT id, username 
        FROM users 
        WHERE username LIKE ? AND id != ?
        LIMIT 10
    ");
    $stmt->bind_param("si", $search, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $target_id = $row['id'];

        // Check relationship status
        $check = $conn->prepare("
            SELECT status FROM friends
            WHERE 
            (sender_id = ? AND receiver_id = ?)
            OR
            (sender_id = ? AND receiver_id = ?)
        ");
        $check->bind_param("iiii", $user_id, $target_id, $target_id, $user_id);
        $check->execute();
        $relation = $check->get_result()->fetch_assoc();

        echo "<div class='user-row'>";
        echo "<a href='../dashboard/profile.php?id=".$target_id."'>"
             .htmlspecialchars($row['username'])."</a>";

        if ($relation) {
            if ($relation['status'] == 'accepted') {
                echo "<span class='badge green'>Already Friends</span>";
            } else {
                echo "<span class='badge orange'>Request Sent</span>";
            }
        } else {
            echo "
            <form action='send_request.php' method='POST'>
                <input type='hidden' name='receiver_id' value='".$target_id."'>
                <button>Connect</button>
            </form>";
        }

        echo "</div>";
    }

    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Find Users</title>
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

h2{
    margin-bottom:20px;
}

input{
    width:100%;
    padding:12px;
    border-radius:20px;
    border:none;
    outline:none;
    background:rgba(255,255,255,0.1);
    color:white;
    margin-bottom:20px;
}

.user-row{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin:12px 0;
    background:#25275a;
    padding:12px 15px;
    border-radius:15px;
    transition:0.3s;
}

.user-row:hover{
    background:rgba(127,179,255,0.15);
    transform:scale(1.02);
}

button{
    background:linear-gradient(90deg,#7fb3ff,#4d94ff);
    color:white;
    border:none;
    padding:8px 14px;
    border-radius:20px;
    cursor:pointer;
    transition:0.3s;
}

button:hover{
    transform:scale(1.05);
}

.badge{
    padding:6px 12px;
    border-radius:20px;
    font-size:12px;
}

.green{
    background:#4CAF50;
}

.orange{
    background:#ff9800;
}

a{
    text-decoration:none;
    color:white;
    font-weight:500;
}

.back{
    display:inline-block;
    margin-top:20px;
    color:#7fb3ff;
}
</style>

<script>
function liveSearch(value){
    if(value.length < 1){
        document.getElementById("results").innerHTML="";
        return;
    }

    fetch("find_users.php?query=" + value)
    .then(response => response.text())
    .then(data => {
        document.getElementById("results").innerHTML = data;
    });
}
</script>

</head>
<body>

<div class="container">
<h2>🔍 Find Friends</h2>

<input type="text" placeholder="Search username..."
onkeyup="liveSearch(this.value)">

<div id="results"></div>

<br>
<a class="back" href="../dashboard/profile.php?id=<?php echo $user_id; ?>">⬅ Back</a>

</div>

</body>
</html>