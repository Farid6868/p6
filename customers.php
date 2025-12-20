<?php
require "includes/config.php";
require "includes/auth.php";

$customers = $dbCustomers
    ->query("SELECT * FROM VpnUser ORDER BY Kod DESC")
    ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fa">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>مشتریان</title>
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
    <h2>لیست مشتریان</h2>

    <!-- Search Row -->
    <div class="search-row">
        <select id="customerSearchMode">
            <option value="all">همه فیلدها</option>
            <option value="name">نام</option>
            <option value="phone">شماره</option>
            <option value="desc">توضیحات</option>
            <option value="moaref">معرف</option>
        </select>

        <input id="customerSearch" placeholder="جستجوی مشتری...">
    </div>

    <div class="table-box">
        <table id="customerTable">
            <thead>
                <tr>
                    <th>کد</th>
                    <th>نام</th>
                    <th>شماره</th>
                    <th>توضیحات</th>
                    <th>معرف</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($customers as $c): ?>
                <tr>
                    <td data-field="code"><?= $c['Kod'] ?></td>
                    <td data-field="name"><?= $c['NameM'] ?></td>
                    <td data-field="phone"><?= $c['SHomare'] ?></td>
                    <td data-field="desc"><?= $c['Tozihat'] ?></td>
                    <td data-field="moaref"><?= $c['Moaref'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</div>

<script src="assets/js/app.js"></script>
<script>
advancedLiveSearch("customerSearch", "customerTable", "customerSearchMode");
</script>

</body>
</html>
