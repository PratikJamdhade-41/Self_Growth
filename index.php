<?php
require_once __DIR__ . "/config/config.php";

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard/dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Self Growth</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background: linear-gradient(135deg,#1e1f4b,#12132e);
    color: white;
}

/* Navbar */
nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 60px;
}

nav h1 {
    font-size: 24px;
    font-weight: bold;
    color: #7fb3ff;
}

nav .nav-links a {
    text-decoration: none;
    margin-left: 20px;
    padding: 8px 18px;
    border-radius: 25px;
    transition: 0.3s;
}

/* Login Button */
.login-btn {
    background: transparent;
    border: 2px solid #7fb3ff;
    color: #7fb3ff;
}

.login-btn:hover {
    background: #7fb3ff;
    color: #12132e;
}

/* Register Button */
.register-btn {
    background: linear-gradient(to right,#7fb3ff,#4d94ff);
    color: white;
    font-weight: bold;
}

.register-btn:hover {
    opacity: 0.85;
}

/* Hero Section */
.hero {
    text-align: center;
    padding: 100px 20px;
}

.hero h2 {
    font-size: 48px;
    margin-bottom: 20px;
    color: #7fb3ff;
}

.hero p {
    font-size: 18px;
    max-width: 600px;
    margin: 0 auto 40px;
    line-height: 1.6;
    color: #ccc;
}

/* CTA Button */
.cta-btn {
    background: linear-gradient(to right,#7fb3ff,#4d94ff);
    color: white;
    padding: 12px 30px;
    font-size: 16px;
    border-radius: 30px;
    text-decoration: none;
    font-weight: bold;
    transition: 0.3s;
}

.cta-btn:hover {
    transform: translateY(-3px);
}

/* Features */
.features {
    background: #1f214a;
    color: white;
    padding: 60px 20px;
    text-align: center;
}

.features h3 {
    font-size: 32px;
    margin-bottom: 40px;
    color: #7fb3ff;
}

.feature-box {
    display: inline-block;
    width: 250px;
    margin: 20px;
}

.feature-box h4 {
    margin-bottom: 10px;
    color: #7fb3ff;
}

.feature-box p {
    color: #bbb;
}

/* Footer */
footer {
    background: #0f1025;
    text-align: center;
    padding: 20px;
    font-size: 14px;
    color: #bbb;
}
</style>
</head>

<body>

<nav>
    <h1>SelfGrowth</h1>
    <div class="nav-links">
        <a href="auth/login.php" class="login-btn">Login</a>
        <a href="auth/register.php" class="register-btn">Register</a>
    </div>
</nav>

<section class="hero">
    <h2>Level Up Your Life 🚀</h2>
    <p>
        Track your goals, build habits, connect with friends,
        and grow together in one powerful productivity platform.
    </p>
    <a href="auth/register.php" class="cta-btn">Get Started</a>
</section>

<section class="features">
    <h3>Why Choose SelfGrowth?</h3>

    <div class="feature-box">
        <h4>🎯 Goal Tracking</h4>
        <p>Set daily tasks and monitor your progress effectively.</p>
    </div>

    <div class="feature-box">
        <h4>👥 Social Growth</h4>
        <p>Connect with friends and motivate each other.</p>
    </div>

    <div class="feature-box">
        <h4>💬 Real-Time Chat</h4>
        <p>Communicate and stay accountable with your circle.</p>
    </div>
</section>

<footer>
    © <?php echo date("Y"); ?> SelfGrowth. All rights reserved.
</footer>

</body>
</html>