<?php
require "includes/config.php";
require "includes/auth.php";

$c = $dbCustomers->query("SELECT COUNT(*) FROM VpnUser")->fetchColumn();
$s = $dbSales->query("SELECT COUNT(*) FROM VpnHesab")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fa">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="wrapper">

<div class="sidebar">
    <h2>CRM</h2>
    <a href="dashboard.php">داشبورد</a>
    <a href="customers.php">مشتریان</a>
    <a href="sales.php">فروش</a>
    <a href="logout.php">خروج</a>
</div>

<div class="content">
    <h1>داشبورد مدیریتی</h1>

    <div class="cards">
        <div class="card">👥 مشتریان<br><b><?= $c ?></b></div>
        <div class="card">💰 فروش‌ها<br><b><?= $s ?></b></div>
    </div>
</div>

</div>

</body>
</html>
