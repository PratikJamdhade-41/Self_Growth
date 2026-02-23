<?php
session_start();
require_once __DIR__ . "/../config/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* Get pending requests */
$stmt = $conn->prepare("
SELECT f.id, u.username
FROM friends f
JOIN users u ON f.sender_id = u.id
WHERE f.receiver_id=? AND f.status='pending'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$requests = $stmt->get_result();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Friend Requests</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            font-family: Arial;
            background: linear-gradient(135deg, #1e1f4b, #12132e);
            padding: 20px;
            color: white;
        }

        /* Card */
        .card {
            background: rgba(31, 33, 74, 0.9);
            padding: 20px;
            border-radius: 15px;
            max-width: 500px;
            margin: auto;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6);
        }

        h2 {
            margin-bottom: 20px;
            color: #7fb3ff;
        }

        /* Rows */
        .row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding: 8px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
        }

        /* Buttons */
        button {
            padding: 6px 12px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            color: white;
            font-size: 13px;
            transition: 0.3s;
        }

        .accept {
            background: #4CAF50;
        }

        .accept:hover {
            background: #3e9e43;
        }

        .reject {
            background: #e74a3b;
        }

        .reject:hover {
            background: #c0392b;
        }

        /* Back link */
        a {
            text-decoration: none;
            color: #7fb3ff;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="card">
        <h2>📩 Friend Requests</h2>

        <?php if ($requests->num_rows > 0): ?>
            <?php while ($req = $requests->fetch_assoc()): ?>
                <div class="row">
                    <span><?php echo htmlspecialchars($req['username']); ?></span>

                    <div>
                        <form action="accept_request.php" method="POST" style="display:inline;">
                            <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                            <button class="accept">Accept</button>
                        </form>

                        <form action="reject_request.php" method="POST" style="display:inline;">
                            <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                            <button class="reject">Reject</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="color:#bbb;">No pending requests.</p>
        <?php endif; ?>

        <br>
        <a href="../dashboard/profile.php?id=<?php echo $user_id; ?>">⬅ Back</a>
    </div>

</body>

</html>