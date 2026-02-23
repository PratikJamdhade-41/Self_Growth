<?php
session_start();
require_once __DIR__ . "/../config/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$current_user = $_SESSION['user_id'];
$profile_id = (int)($_GET['id'] ?? 0);

if (!$profile_id) {
    header("Location: dashboard.php");
    exit();
}

/* Update activity */
$update = $conn->prepare("UPDATE users SET last_active=NOW() WHERE id=?");
$update->bind_param("i", $current_user);
$update->execute();

/* FETCH PROFILE USER */
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "User not found.";
    exit();
}

$user = $result->fetch_assoc();

/* CHECK RELATIONSHIP */
$check = $conn->prepare("
SELECT id, status FROM friends
WHERE 
(sender_id=? AND receiver_id=?)
OR
(sender_id=? AND receiver_id=?)
");
$check->bind_param("iiii", $current_user, $profile_id, $profile_id, $current_user);
$check->execute();
$relation = $check->get_result()->fetch_assoc();

/* MUTUAL FRIENDS */
$mutual = $conn->prepare("
SELECT COUNT(*) as total
FROM friends f1
JOIN friends f2
ON f1.sender_id = f2.sender_id
WHERE f1.receiver_id=? 
AND f2.receiver_id=? 
AND f1.status='accepted'
AND f2.status='accepted'
");
$mutual->bind_param("ii", $current_user, $profile_id);
$mutual->execute();
$mutual_count = $mutual->get_result()->fetch_assoc()['total'];

/* MY STATS */
$stmt = $conn->prepare("
SELECT strength, intelligence, discipline, focus 
FROM users WHERE id=?
");
$stmt->bind_param("i", $current_user);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();

/* FRIEND LIST */
$friends_stmt = $conn->prepare("
SELECT u.id, u.username, u.last_active
FROM users u
JOIN friends f
ON (
    (u.id = f.sender_id AND f.receiver_id=?)
    OR
    (u.id = f.receiver_id AND f.sender_id=?)
)
WHERE f.status='accepted'
");
$friends_stmt->bind_param("ii", $profile_id, $profile_id);
$friends_stmt->execute();
$friends_result = $friends_stmt->get_result();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1e1f4b, #12132e);
            color: white;
            min-height: 100vh;
            padding: 40px 20px;
        }

        /* Main Container */
        .profile-container {
            max-width: 1000px;
            margin: auto;
            background: #1f214a;
            border-radius: 25px;
            padding: 35px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.5);
        }

        /* Header */
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .profile-header h2 {
            font-weight: 600;
            font-size: 24px;
        }

        /* Top Buttons */
        .top-buttons {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .top-buttons a {
            background: linear-gradient(90deg, #7fb3ff, #4d94ff);
            padding: 8px 14px;
            border-radius: 20px;
            text-decoration: none;
            color: white;
            font-size: 13px;
            transition: 0.3s;
        }

        .top-buttons a:hover {
            transform: scale(1.05);
        }

        /* Sections */
        .section {
            background: #25275a;
            padding: 20px;
            border-radius: 18px;
            margin-top: 20px;
        }

        .section h3 {
            margin-bottom: 15px;
            color: #aaa;
        }

        /* Stats Bars */
        .stat {
            margin-bottom: 12px;
        }

        .stat-label {
            font-size: 13px;
            margin-bottom: 5px;
        }

        .stat-bar {
            background: rgba(255, 255, 255, 0.1);
            height: 8px;
            border-radius: 10px;
            overflow: hidden;
        }

        .stat-fill {
            height: 8px;
            background: linear-gradient(90deg, #7fb3ff, #4d94ff);
        }

        /* Buttons */
        button {
            padding: 8px 14px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 13px;
            margin-right: 8px;
            transition: 0.3s;
        }

        .add {
            background: #4CAF50;
            color: white;
        }

        .remove {
            background: #e74a3b;
            color: white;
        }

        .pending {
            background: #ff9800;
            color: white;
        }

        .chat {
            background: #36b9cc;
            color: white;
        }

        button:hover {
            transform: scale(1.05);
        }

        /* Friend List */
        .friend-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            margin-bottom: 8px;
        }

        .friend-row:hover {
            background: rgba(127, 179, 255, 0.15);
        }

        .friend-row a {
            color: white;
            text-decoration: none;
            font-weight: 500;
        }

        .online {
            color: #4CAF50;
            font-size: 12px;
        }

        .offline {
            color: #888;
            font-size: 12px;
        }

        /* Back */
        .back {
            margin-top: 20px;
            display: inline-block;
            text-decoration: none;
            color: #7fb3ff;
        }

        @media(max-width:768px) {
            .profile-container {
                padding: 20px;
            }
        }
    </style>
</head>

<body>

    <div class="profile-container">

        <div class="profile-header">
            <h2>🎮 <?php echo htmlspecialchars($user['username']); ?></h2>
            <div>
                LV <?php echo $user['level']; ?> | XP <?php echo $user['xp']; ?>
            </div>
        </div>

        <?php if ($current_user == $profile_id): ?>
            <div class="top-buttons">
                <a href="../friends/find_users.php">🔎 Find Friends</a>
                <a href="../friends/friend_requests.php">📩 Requests</a>
            </div>
        <?php endif; ?>

        <div class="section">
            <h3>📊 Player Stats</h3>

            <div class="stat">
                <div class="stat-label">💪 Strength (<?php echo $user['strength']; ?>)</div>
                <div class="stat-bar">
                    <div class="stat-fill" style="width:<?php echo $user['strength']; ?>%"></div>
                </div>
            </div>

            <div class="stat">
                <div class="stat-label">🧠 Intelligence (<?php echo $user['intelligence']; ?>)</div>
                <div class="stat-bar">
                    <div class="stat-fill" style="width:<?php echo $user['intelligence']; ?>%"></div>
                </div>
            </div>

            <div class="stat">
                <div class="stat-label">🔥 Discipline (<?php echo $user['discipline']; ?>)</div>
                <div class="stat-bar">
                    <div class="stat-fill" style="width:<?php echo $user['discipline']; ?>%"></div>
                </div>
            </div>

            <div class="stat">
                <div class="stat-label">🎯 Focus (<?php echo $user['focus']; ?>)</div>
                <div class="stat-bar">
                    <div class="stat-fill" style="width:<?php echo $user['focus']; ?>%"></div>
                </div>
            </div>

        </div>

        <div class="section">
            <h3>🤝 Connection</h3>

            <?php if ($current_user != $profile_id): ?>

                <?php if (!$relation): ?>
                    <form action="send_request.php" method="POST">
                        <input type="hidden" name="receiver_id" value="<?php echo $profile_id; ?>">
                        <button class="add">Add Friend</button>
                    </form>

                <?php elseif ($relation['status'] == 'pending'): ?>
                    <button class="pending" disabled>Request Sent</button>

                <?php else: ?>
                    <form action="remove_friend.php" method="POST">
                        <input type="hidden" name="friend_id" value="<?php echo $relation['id']; ?>">
                        <button class="remove">Remove</button>
                    </form>
                    <a href="chat.php?id=<?php echo $profile_id; ?>">
                        <button class="chat">Message</button>
                    </a>
                <?php endif; ?>

            <?php endif; ?>

        </div>

        <div class="section">
            <h3>👥 Friends</h3>

            <?php if ($friends_result->num_rows > 0): ?>
                <?php while ($friend = $friends_result->fetch_assoc()):

                    $is_online = false;
                    if ($friend['last_active'] && time() - strtotime($friend['last_active']) < 30) {
                        $is_online = true;
                    }
                ?>

                    <div class="friend-row">

                        <div>
                            <a href="profile.php?id=<?php echo $friend['id']; ?>">
                                <?php echo htmlspecialchars($friend['username']); ?>
                            </a>
                            <br>
                            <?php if ($is_online): ?>
                                <span class="online">● Online</span>
                            <?php else: ?>
                                <span class="offline">● Offline</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($current_user == $profile_id): ?>
                            <a href="../chat/chat.php?id=<?php echo $friend['id']; ?>">
                                <button class="chat">Chat</button>
                            </a>
                        <?php endif; ?>

                    </div>

                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:#888;">No friends yet.</p>
            <?php endif; ?>

        </div>

        <a class="back" href="dashboard.php">⬅ Back to Dashboard</a>

    </div>

</body>

</html>