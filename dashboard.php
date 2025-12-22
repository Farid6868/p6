<?php
require "includes/config.php";
require "includes/auth.php";

$c = $dbCustomers->query("SELECT COUNT(*) FROM VpnUser")->fetchColumn();
$s = $dbSales->query("SELECT COUNT(*) FROM VpnHesab")->fetchColumn();

// محاسبه فروش امروز (اگر ستون تاریخ وجود دارد)
$todaySales = 0;
try {
    // ابتدا بررسی می‌کنیم جدول چه ستون‌هایی دارد
    $columns = $dbSales->query("PRAGMA table_info(VpnHesab)")->fetchAll(PDO::FETCH_ASSOC);
    $dateColumn = null;
    
    foreach ($columns as $col) {
        if (in_array(strtolower($col['name']), ['date', 'tarikh', 'تاریخ', 'created_at'])) {
            $dateColumn = $col['name'];
            break;
        }
    }
    
    if ($dateColumn) {
        $todaySales = $dbSales->query("SELECT COUNT(*) FROM VpnHesab WHERE $dateColumn >= date('now')")->fetchColumn();
    }
} catch(Exception $e) {
    $todaySales = 0;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد پیشرفته</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="wrapper">

    <!-- سایدبار مدرن -->
    <div class="sidebar">
        <h2><i class="fas fa-sliders-h"></i> پنل مدیریت</h2>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> داشبورد</a>
        <a href="customers.php"><i class="fas fa-users"></i> مشتریان</a>
        <a href="sales.php"><i class="fas fa-chart-line"></i> فروش</a>
        <!-- این خط را اضافه کنید -->
        <a href="settings.php"><i class="fas fa-cog"></i> تنظیمات</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> خروج</a>
    </div>

    <!-- محتوای اصلی -->
    <div class="content">
        <h1>داشبورد مدیریتی <span style="font-size: 1.5rem; color: #38bdf8;">| خلاصه عملکرد</span></h1>
        <p style="color: #94a3b8; margin-bottom: 30px;">آمار کلی سیستم را در کارت‌های زیر مشاهده می‌کنید.</p>

        <!-- کارت‌های آمار -->
        <div class="cards">
            <div class="card">
                <i class="fas fa-user-friends" style="font-size: 2.5rem; color: #38bdf8;"></i>
                <div style="margin-top: 15px; font-size: 1.1rem;">کل مشتریان</div>
                <b><?= $c ?></b>
                <div style="margin-top: 10px; font-size: 0.9rem; color: #94a3b8;">
                    <i class="fas fa-arrow-up" style="color: #10b981;"></i> کل کاربران ثبت‌شده
                </div>
            </div>

            <div class="card">
                <i class="fas fa-shopping-cart" style="font-size: 2.5rem; color: #818cf8;"></i>
                <div style="margin-top: 15px; font-size: 1.1rem;">کل فروش‌ها</div>
                <b><?= $s ?></b>
                <div style="margin-top: 10px; font-size: 0.9rem; color: #94a3b8;">
                    <i class="fas fa-chart-bar" style="color: #f59e0b;"></i> تمام تراکنش‌ها
                </div>
            </div>

            <div class="card">
                <i class="fas fa-bolt" style="font-size: 2.5rem; color: #f59e0b;"></i>
                <div style="margin-top: 15px; font-size: 1.1rem;">فروش امروز</div>
                <b><?= $todaySales ?></b>
                <div style="margin-top: 10px; font-size: 0.9rem; color: #94a3b8;">
                    <i class="fas fa-calendar-day" style="color: #ef4444;"></i> 
                    <?= ($todaySales > 0) ? 'تراکنش‌های روز جاری' : 'آماده برای ثبت' ?>
                </div>
            </div>

            <div class="card">
                <i class="fas fa-percentage" style="font-size: 2.5rem; color: #10b981;"></i>
                <div style="margin-top: 15px; font-size: 1.1rem;">نرخ رشد</div>
                <b>+12.5%</b>
                <div style="margin-top: 10px; font-size: 0.9rem; color: #94a3b8;">
                    <i class="fas fa-trend-up" style="color: #10b981;"></i> نسبت به ماه گذشته
                </div>
            </div>
        </div>

        <!-- بخش پایینی داشبورد -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px; margin-top: 40px;">
            <div style="background: rgba(15, 23, 42, 0.6); padding: 25px; border-radius: 20px; border: 1px solid #334155;">
                <h3><i class="fas fa-history"></i> فعالیت‌های اخیر</h3>
                <ul style="margin-top: 15px; list-style: none; color: #cbd5e1;">
                    <li style="padding: 10px 0; border-bottom: 1px solid #334155;">
                        <i class="fas fa-user-plus" style="color: #38bdf8;"></i> کاربر جدید ثبت شد.
                    </li>
                    <li style="padding: 10px 0; border-bottom: 1px solid #334155;">
                        <i class="fas fa-credit-card" style="color: #10b981;"></i> فروش جدید تکمیل شد.
                    </li>
                    <li style="padding: 10px 0; border-bottom: 1px solid #334155;">
                        <i class="fas fa-database" style="color: #f59e0b;"></i> پشتیبان‌گیری از دیتابیس انجام شد.
                    </li>
                    <li style="padding: 10px 0;">
                        <i class="fas fa-cog" style="color: #818cf8;"></i> سیستم به‌روزرسانی گردید.
                    </li>
                </ul>
            </div>

            <div style="background: rgba(15, 23, 42, 0.6); padding: 25px; border-radius: 20px; border: 1px solid #334155;">
                <h3><i class="fas fa-info-circle"></i> راهنمای سریع</h3>
                <p style="margin-top: 15px; color: #94a3b8; line-height: 1.8;">
                    برای مدیریت بهتر سیستم:
                    <br>✅ روی ردیف‌های جدول <strong>کلیک کنید</strong> تا جزئیات را ببینید.
                    <br>✅ از نوار جستجو برای <strong>فیلتر کردن</strong> استفاده نمایید.
                    <br>✅ پس از کار، از <strong>خروج</strong> استفاده کنید.
                </p>
                <button style="width: 100%; padding: 12px; margin-top: 20px; background: linear-gradient(90deg, #38bdf8, #818cf8); color: white; border: none; border-radius: 10px; cursor: pointer;" onclick="alert('راهنمای کامل در حال آماده‌سازی است...')">
                    <i class="fas fa-question-circle"></i> راهنمای کامل
                </button>
            </div>
        </div>
    </div>
</div>

</body>
</html>