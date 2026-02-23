<?php
session_start();
require_once __DIR__ . "/../config/config.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "All fields are required.";
    } else {

        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Username already taken.";
        } else {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $level = 1;
            $xp = 0;
            $strength = 0;
            $intelligence = 0;
            $discipline = 0;
            $focus = 0;
            $streak = 0;
            $restore_key = 0;
            $is_public = 1;

            $stmt = $conn->prepare("
                INSERT INTO users 
                (username, password, level, xp, strength, intelligence, discipline, focus, streak, restore_key, is_public)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "ssiiiiiiiii",
                $username,
                $hashed_password,
                $level,
                $xp,
                $strength,
                $intelligence,
                $discipline,
                $focus,
                $streak,
                $restore_key,
                $is_public
            );

            if ($stmt->execute()) {
                header("Location: login.php");
                exit();
            } else {
                $error = "Something went wrong.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', sans-serif;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background: linear-gradient(135deg,#1e1f4b,#12132e);
    overflow: hidden;
}

/* Background blur circles */
body::before,
body::after {
    content: "";
    position: absolute;
    border-radius: 50%;
    filter: blur(100px);
}

body::before {
    width: 320px;
    height: 320px;
    background: #4d94ff;
    top: 10%;
    left: 10%;
}

body::after {
    width: 350px;
    height: 350px;
    background: #7fb3ff;
    bottom: 10%;
    right: 15%;
}

/* Card */
.card {
    position: relative;
    width: 400px;
    max-width: 92%;
    padding: 40px 30px;
    background: rgba(31,33,74,0.85);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    border: 1px solid rgba(255,255,255,0.1);
    box-shadow: 0 20px 40px rgba(0,0,0,0.6);
    text-align: center;
    z-index: 2;
    color: white;
}

h2 {
    letter-spacing: 1px;
    margin-bottom: 25px;
    color: #7fb3ff;
}

/* Inputs */
.input-group {
    margin-bottom: 18px;
    text-align: left;
}

.input-group label {
    font-size: 14px;
    color: #bbb;
    display: block;
    margin-bottom: 6px;
}

input {
    width: 100%;
    padding: 12px;
    border-radius: 10px;
    border: none;
    background: rgba(255,255,255,0.08);
    font-size: 14px;
    color: white;
}

input:focus {
    outline: none;
    box-shadow: 0 0 8px #4d94ff;
}

/* Button */
button {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 30px;
    background: linear-gradient(to right, #7fb3ff, #4d94ff);
    color: white;
    font-size: 15px;
    cursor: pointer;
    transition: 0.3s ease;
}

button:hover {
    transform: scale(1.05);
}

/* Links */
p {
    margin-top: 15px;
    font-size: 14px;
    color: #bbb;
}

a {
    text-decoration: none;
    color: #7fb3ff;
}

a:hover {
    text-decoration: underline;
}

/* Error */
.error {
    color: #ff6b6b;
    margin-bottom: 15px;
    font-size: 14px;
}

/* Mobile */
@media (max-width: 480px) {
    .card {
        padding: 30px 20px;
    }

    h2 {
        font-size: 20px;
    }

    input {
        padding: 10px;
    }

    button {
        padding: 10px;
    }
}

</style>
</head>

<body>

<div class="card">

<h2>Create Account</h2>

<?php if (!empty($error)): ?>
    <div class="error"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST">

    <div class="input-group">
        <label>Username</label>
        <input type="text" name="username" required>
    </div>

    <div class="input-group">
        <label>Password</label>
        <input type="password" name="password" required>
    </div>

    <button type="submit">Register</button>

</form>

<p>Already have an account? <a href="login.php">Login</a></p>

</div>

</body>
</html>