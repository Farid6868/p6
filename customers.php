<?php
require "includes/config.php";
require "includes/auth.php";

$customers = $dbCustomers
    ->query("SELECT * FROM VpnUser ORDER BY Kod DESC")
    ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>مشتریان</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="wrapper">

<div class="sidebar">
    <h2><i class="fas fa-sliders-h"></i> پنل مدیریت</h2>
    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> داشبورد</a>
    <a href="customers.php"><i class="fas fa-users"></i> مشتریان</a>
    <a href="sales.php"><i class="fas fa-chart-line"></i> فروش</a>
    <!-- این خط را اضافه کنید -->
    <a href="settings.php"><i class="fas fa-cog"></i> تنظیمات</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> خروج</a>
</div>

<div class="content">
    <h2>لیست مشتریان <small style="color: #94a3b8; font-size: 0.9rem;">(<?= count($customers) ?> مشتری)</small></h2>

    <!-- Search Row -->
    <div class="search-row">
        <select id="customerSearchMode">
            <option value="all">همه فیلدها</option>
            <option value="name">نام</option>
            <option value="phone">شماره</option>
            <option value="desc">توضیحات</option>
            <option value="moaref">معرف</option>
            <option value="code">کد</option>
        </select>

        <input id="customerSearch" placeholder="جستجوی مشتری... (نام، شماره یا کد)">
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
            <?php if (count($customers) > 0): ?>
                <?php foreach ($customers as $c): ?>
                <tr data-raw='<?= json_encode($c, JSON_UNESCAPED_UNICODE) ?>'>
                    <td data-field="code"><?= htmlspecialchars($c['Kod'] ?? '') ?></td>
                    <td data-field="name"><?= htmlspecialchars($c['NameM'] ?? '') ?></td>
                    <td data-field="phone"><?= htmlspecialchars($c['SHomare'] ?? '') ?></td>
                    <td data-field="desc"><?= htmlspecialchars($c['Tozihat'] ?? '') ?></td>
                    <td data-field="moaref"><?= htmlspecialchars($c['Moaref'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 30px; color: #94a3b8;">
                        هیچ مشتری ثبت نشده است
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</div>

<script src="assets/js/app.js"></script>
<script>
// بعد از لود کامل صفحه
document.addEventListener('DOMContentLoaded', function() {
    console.log('صفحه مشتریان لود شد');
    
    // فعال کردن جستجو
    advancedLiveSearch("customerSearch", "customerTable", "customerSearchMode");
    
    // فعال کردن کلیک روی ردیف‌های جدول مشتریان
    setupTableRowClicks('customerTable', false);
    
    // برای دیباگ
    console.log('جدول مشتریان:', document.getElementById('customerTable'));
});
</script>

</body>
</html>