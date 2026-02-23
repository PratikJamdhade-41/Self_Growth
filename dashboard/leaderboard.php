<?php
session_start();
require_once __DIR__ . "/../config/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$view = $_GET['view'] ?? 'global';

/* ===============================
   GLOBAL LEADERBOARD
=================================*/
if ($view == "friends") {

    $stmt = $conn->prepare("
        SELECT DISTINCT u.id, u.username, u.level, u.xp, u.streak
        FROM users u
        JOIN friends f
        ON (u.id = f.sender_id OR u.id = f.receiver_id)
        WHERE 
        (
            (f.sender_id = ? OR f.receiver_id = ?)
            AND f.status = 'accepted'
        )
        OR u.id = ?
        ORDER BY u.level DESC, u.xp DESC
    ");

    $stmt->bind_param("iii", $user_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

} else {

    $result = $conn->query("
        SELECT id, username, level, xp, streak
        FROM users
        WHERE is_public = 1
        ORDER BY level DESC, xp DESC
    ");
}

$position = 1;
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Leaderboard</title>

<style>
*{margin:0;padding:0;box-sizing:border-box;}

body{
    font-family:'Segoe UI',sans-serif;
    background:linear-gradient(135deg,#1e1f4b,#12132e);
    min-height:100vh;
    padding:40px 20px;
    color:white;
}

/* Main Container */
.container{
    max-width:1000px;
    margin:auto;
    background:#1f214a;
    border-radius:25px;
    padding:35px;
    box-shadow:0 30px 80px rgba(0,0,0,0.5);
}

/* Header */
.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
}

.header h2{
    font-weight:600;
}

/* Buttons */
.btn{
    padding:8px 14px;
    border-radius:20px;
    text-decoration:none;
    font-size:13px;
    margin-right:10px;
    background:linear-gradient(90deg,#7fb3ff,#4d94ff);
    color:white;
    transition:0.3s;
}

.btn:hover{
    transform:scale(1.05);
}

/* Card Row */
.card{
    background:#25275a;
    padding:15px 20px;
    border-radius:15px;
    margin-bottom:12px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    transition:0.3s;
}

.card:hover{
    background:rgba(127,179,255,0.15);
    transform:translateX(5px);
}

/* Rank */
.rank{
    font-weight:bold;
    font-size:18px;
    width:70px;
}

/* Top 3 */
.top1{
    border-left:5px solid gold;
}
.top2{
    border-left:5px solid silver;
}
.top3{
    border-left:5px solid #cd7f32;
}

.crown{
    margin-right:5px;
}

/* Stats */
.user-info{
    flex:1;
}

.stats{
    font-size:13px;
    color:#ccc;
}

/* Back Button */
.back{
    margin-top:20px;
    display:inline-block;
    color:#7fb3ff;
    text-decoration:none;
}

@media(max-width:768px){
    .card{
        flex-direction:column;
        align-items:flex-start;
        gap:6px;
    }
}
</style>
</head>

<body>

<div class="container">

<div class="header">
    <h2>🏆 Leaderboard</h2>
    <div>
        <a class="btn" href="?view=global">🌍 Global</a>
        <a class="btn" href="?view=friends">🤝 Friends</a>
    </div>
</div>

<?php if ($result && $result->num_rows > 0): ?>

<?php while($row = $result->fetch_assoc()): ?>

<?php
$row_class = "";
if($position == 1) $row_class = "top1";
elseif($position == 2) $row_class = "top2";
elseif($position == 3) $row_class = "top3";
?>

<div class="card <?php echo $row_class; ?>">

    <div class="rank">
        <?php if($position == 1): ?>
            <span class="crown">👑</span>
        <?php endif; ?>
        #<?php echo $position; ?>
    </div>

    <div class="user-info">
        <strong><?php echo htmlspecialchars($row['username']); ?></strong>
        <div class="stats">
            Level <?php echo $row['level']; ?> |
            XP <?php echo $row['xp']; ?> |
            🔥 <?php echo $row['streak']; ?>
        </div>
    </div>

</div>

<?php 
$position++;
endwhile; 
?>

<?php else: ?>
<p style="color:#aaa;">No users found.</p>
<?php endif; ?>

<br>
<a class="back" href="dashboard.php">⬅ Back to Dashboard</a>

</div>

</body>
</html>