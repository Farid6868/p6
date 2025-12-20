<?php
require "includes/config.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    if ($user === "admin" && $pass === "1234") {
        $_SESSION['login'] = true;
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "نام کاربری یا رمز عبور اشتباه است";
    }
}
?>
<!DOCTYPE html>
<html lang="fa">
<head>
<meta charset="UTF-8">
<title>ورود</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">

<form method="post" class="login-box">
    <h2>پنل مدیریت</h2>
    <input name="username" placeholder="نام کاربری">
    <input name="password" type="password" placeholder="رمز عبور">
    <button>ورود</button>
    <div class="error"><?= $error ?></div>
</form>

</body>
</html>
