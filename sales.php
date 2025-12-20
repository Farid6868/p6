<?php
require "includes/config.php";
require "includes/auth.php";

$sales = $dbSales
    ->query("SELECT * FROM VpnHesab ORDER BY Kod DESC")
    ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fa">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>فروش</title>
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
    <h2>لیست فروش</h2>

    <!-- Search Row -->
    <div class="search-row">
        <select id="salesSearchMode">
            <option value="all">همه فیلدها</option>
            <option value="kodm">کد مشتری</option>
            <option value="usera">یوزر</option>
            <option value="account">اکانت</option>
            <option value="price">مبلغ</option>
        </select>

        <input id="salesSearch" placeholder="جستجوی فروش...">
    </div>

    <div class="table-box">
        <table id="salesTable">
            <thead>
                <tr>
                    <th>کد مشتری</th>
                    <th>یوزر</th>
                    <th>اکانت</th>
                    <th>مبلغ</th>
                    <th>تاریخ</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($sales as $s): ?>
                <tr>
                    <td data-field="kodm"><?= $s['KodM'] ?></td>
                    <td data-field="usera"><?= $s['UserA'] ?></td>
                    <td data-field="account"><?= $s['Account'] ?></td>
                    <td data-field="price"><?= $s['Froush'] ?></td>
                    <td data-field="date"><?= $s['Tarikh'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</div>

<script src="assets/js/app.js"></script>
<script>
advancedLiveSearch("salesSearch", "salesTable", "salesSearchMode");
</script>

</body>
</html>
