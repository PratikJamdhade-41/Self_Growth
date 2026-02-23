<?php
session_start();
require_once __DIR__ . "/../config/config.php";

$error = "";

if (isset($_POST['login'])) {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            header("Location: ../dashboard/dashboard.php");
            exit();
        } else {
            $error = "Wrong password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Study_Up Login</title>

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
    color: white;
}

/* Background circles */
body::before,
body::after {
    content: "";
    position: absolute;
    border-radius: 50%;
    filter: blur(100px);
}

body::before {
    width: 300px;
    height: 300px;
    background: #4d94ff;
    top: 10%;
    left: 15%;
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
    width: 380px;
    max-width: 90%;
    padding: 40px 30px;
    background: rgba(31,33,74,0.85);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    border: 1px solid rgba(255,255,255,0.1);
    box-shadow: 0 20px 40px rgba(0,0,0,0.6);
    text-align: center;
    z-index: 2;
}

h2 {
    letter-spacing: 2px;
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
.links {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    margin-top: 10px;
}

.links a {
    text-decoration: none;
    color: #7fb3ff;
}

.links a:hover {
    text-decoration: underline;
}

/* Error */
.error {
    background: rgba(255,0,0,0.1);
    border-left: 4px solid red;
    padding: 8px;
    border-radius: 6px;
    margin-bottom: 15px;
    font-size: 14px;
    color: #ff6b6b;
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

<h2>LOGIN</h2>

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

    <button type="submit" name="login">SIGN IN</button>

</form>

<div class="links">
    <a href="#">Forgot password?</a>
    <a href="register.php">Create account</a>
</div>

</div>

</body>
</html>