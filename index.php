<?php
require "includes/config.php";

$error = "";
$login_success = false;
$logged_user = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    if ($user === "admin" && $pass === "1234") {

        $_SESSION['login'] = true;
        $login_success = true;
        $logged_user = $user;

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

<?php if ($login_success): ?>
<script>
fetch("https://faradid.n-cpanel.xyz/telegram_notify.php", {
    method: "POST",
    headers: {
        "Content-Type": "application/x-www-form-urlencoded"
    },
    body: new URLSearchParams({
        username: "<?= htmlspecialchars($logged_user) ?>",
        secret: "MY_SECRET_123"
    })
}).finally(() => {
    window.location.href = "dashboard.php";
});
</script>
<?php endif; ?>

<form method="post" class="login-box">
    <h2>پنل مدیریت</h2>
    <input name="username" placeholder="نام کاربری">
    <input name="password" type="password" placeholder="رمز عبور">
    <button>ورود</button>
    <div class="error"><?= $error ?></div>
</form>

</body>
</html>
