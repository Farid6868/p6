<?php
require "includes/config.php";
require "includes/auth.php";

// تنظیمات پیش‌فرض
$defaultSettings = [
    'hide_purchase_price' => false,
    'show_customer_name' => true
];

// بارگذاری تنظیمات از کوکی یا استفاده از پیش‌فرض
$settings = $defaultSettings;
if (isset($_COOKIE['crm_settings'])) {
    $cookieSettings = json_decode($_COOKIE['crm_settings'], true);
    if ($cookieSettings) {
        $settings = array_merge($defaultSettings, $cookieSettings);
    }
}

// ذخیره تنظیمات جدید
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newSettings = [
        'hide_purchase_price' => isset($_POST['hide_purchase_price']) ? true : false,
        'show_customer_name' => isset($_POST['show_customer_name']) ? true : false
    ];
    
    // ذخیره در کوکی (به مدت 30 روز)
    setcookie('crm_settings', json_encode($newSettings), time() + (30 * 24 * 60 * 60), '/');
    $settings = $newSettings;
    
    // ریدایرکت با پیام موفقیت
    header('Location: settings.php?success=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تنظیمات سیستم</title>
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
    <a href="settings.php" class="active"><i class="fas fa-cog"></i> تنظیمات</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> خروج</a>
</div>

<div class="content">
    <h2><i class="fas fa-cog"></i> تنظیمات سیستم</h2>
    
    <?php if (isset($_GET['success'])): ?>
    <div style="background: rgba(16, 185, 129, 0.2); color: #10b981; padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #10b981;">
        <i class="fas fa-check-circle"></i> تنظیمات با موفقیت ذخیره شدند.
    </div>
    <?php endif; ?>
    
    <form method="post" class="settings-form">
        <div class="setting-card">
            <h3><i class="fas fa-eye-slash"></i> تنظیمات نمایش</h3>
            
            <div class="setting-item">
                <label class="toggle-switch">
                    <input type="checkbox" name="hide_purchase_price" id="hide_purchase_price" 
                           <?= $settings['hide_purchase_price'] ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </label>
                <div class="setting-info">
                    <h4>مخفی کردن قیمت خرید</h4>
                    <p>اگر فعال باشد، ستون "قیمت خرید" در جدول فروش نمایش داده نمی‌شود.</p>
                </div>
                <div class="setting-icon">
                    <i class="fas fa-money-bill-wave" style="color: #ef4444;"></i>
                </div>
            </div>
            
            <div class="setting-item">
                <label class="toggle-switch">
                    <input type="checkbox" name="show_customer_name" id="show_customer_name" 
                           <?= $settings['show_customer_name'] ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </label>
                <div class="setting-info">
                    <h4>نمایش نام مشتری</h4>
                    <p>در صفحه فروش، نام مشتری در کنار کد مشتری نمایش داده می‌شود.</p>
                </div>
                <div class="setting-icon">
                    <i class="fas fa-user-tag" style="color: #38bdf8;"></i>
                </div>
            </div>
        </div>
        
        <div class="setting-card">
            <h3><i class="fas fa-search"></i> تنظیمات جستجو</h3>
            
            <div class="setting-item">
                <div class="setting-info">
                    <h4>جستجوی دقیق کد</h4>
                    <p>در جستجوی کد مشتری، فقط کدهای دقیقا برابر نمایش داده می‌شوند.</p>
                </div>
                <div class="setting-icon">
                    <i class="fas fa-equals" style="color: #10b981;"></i>
                </div>
            </div>
        </div>
        
        <button type="submit" class="save-btn">
            <i class="fas fa-save"></i> ذخیره تنظیمات
        </button>
    </form>
</div>

</div>

<style>
.settings-form {
    max-width: 800px;
    margin-top: 20px;
}

.setting-card {
    background: rgba(30, 41, 59, 0.6);
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 25px;
    border: 1px solid #334155;
}

.setting-card h3 {
    color: #38bdf8;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #334155;
    font-size: 1.3rem;
}

.setting-item {
    display: flex;
    align-items: center;
    padding: 20px;
    background: rgba(15, 23, 42, 0.5);
    border-radius: 12px;
    margin-bottom: 15px;
    border: 1px solid rgba(51, 65, 85, 0.3);
    transition: all 0.3s;
}

.setting-item:hover {
    background: rgba(15, 23, 42, 0.7);
    border-color: #475569;
}

.toggle-switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
    margin-left: 20px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #475569;
    transition: .4s;
    border-radius: 34px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: #38bdf8;
}

input:checked + .toggle-slider:before {
    transform: translateX(26px);
}

.setting-info {
    flex: 1;
}

.setting-info h4 {
    color: #e2e8f0;
    margin-bottom: 5px;
    font-size: 1.1rem;
}

.setting-info p {
    color: #94a3b8;
    font-size: 0.9rem;
    line-height: 1.5;
}

.setting-icon {
    font-size: 2rem;
    margin-right: 20px;
    opacity: 0.8;
}

.save-btn {
    background: linear-gradient(90deg, #10b981, #38bdf8);
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 10px;
    font-size: 1.1rem;
    font-weight: bold;
    cursor: pointer;
    width: 100%;
    margin-top: 20px;
    transition: all 0.3s;
}

.save-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(56, 189, 248, 0.3);
}

.save-btn i {
    margin-left: 10px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // اضافه کردن لینک تنظیمات به سایدبار سایر صفحات
    const sidebarLinks = document.querySelectorAll('.sidebar a');
    const settingsLink = Array.from(sidebarLinks).find(a => a.textContent.includes('فروش'));
    
    if (settingsLink) {
        const li = document.createElement('li');
        li.innerHTML = '<a href="settings.php"><i class="fas fa-cog"></i> تنظیمات</a>';
        settingsLink.parentNode.insertBefore(li, settingsLink.nextSibling);
    }
});
</script>

</body>
</html>