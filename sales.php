<?php
require "includes/config.php";
require "includes/auth.php";

// بارگذاری تنظیمات
$settings = [
    'hide_purchase_price' => false,
    'show_customer_name' => true
];

if (isset($_COOKIE['crm_settings'])) {
    $cookieSettings = json_decode($_COOKIE['crm_settings'], true);
    if ($cookieSettings) {
        $settings = array_merge($settings, $cookieSettings);
    }
}

// ========== گرفتن ساختار واقعی دیتابیس ==========
// ستون‌های جدول VpnHesab
$columnsStmt = $dbSales->query("PRAGMA table_info(VpnHesab)");
$dbColumns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);

// نام ستون‌های واقعی
$columnNames = [];
foreach ($dbColumns as $col) {
    $columnNames[] = $col['name'];
}

// ========== شناسایی ستون‌های مهم ==========
$customerCodeColumn = null;
$purchasePriceColumn = null;
$salePriceColumn = null;
$dateColumn = null;
$statusColumn = null;
$userAColumn = null;
$accountColumn = null;

// اولویت‌بندی برای شناسایی ستون‌ها
$purchaseKeywords = ['kharid', 'خرید', 'purchase', 'مبلغ_خرید', 'قیمت_خرید'];
$saleKeywords = ['froush', 'فروش', 'sale', 'مبلغ_فروش', 'قیمت_فروش', 'mablagh'];
$dateKeywords = ['tarikh', 'تاریخ', 'date', 'زمان'];

foreach ($columnNames as $col) {
    $lowerCol = strtolower($col);
    
    if (strpos($lowerCol, 'kodm') !== false || strpos($lowerCol, 'کدم') !== false) {
        $customerCodeColumn = $col;
    }
    if (strpos($lowerCol, 'customer') !== false || strpos($lowerCol, 'مشتری') !== false) {
        if (!$customerCodeColumn) $customerCodeColumn = $col;
    }
    
    // شناسایی ستون خرید
    if (!$purchasePriceColumn) {
        foreach ($purchaseKeywords as $keyword) {
            if (strpos($lowerCol, $keyword) !== false) {
                $purchasePriceColumn = $col;
                break;
            }
        }
    }
    
    // شناسایی ستون فروش
    if (!$salePriceColumn) {
        foreach ($saleKeywords as $keyword) {
            if (strpos($lowerCol, $keyword) !== false) {
                $salePriceColumn = $col;
                break;
            }
        }
    }
    
    // شناسایی ستون تاریخ
    if (!$dateColumn) {
        foreach ($dateKeywords as $keyword) {
            if (strpos($lowerCol, $keyword) !== false) {
                $dateColumn = $col;
                break;
            }
        }
    }
    
    if (strpos($lowerCol, 'status') !== false || strpos($lowerCol, 'vaziat') !== false || 
        strpos($lowerCol, 'وضعیت') !== false) {
        $statusColumn = $col;
    }
    if (strpos($lowerCol, 'usera') !== false || $col === 'UserA') {
        $userAColumn = $col;
    }
    if (strpos($lowerCol, 'account') !== false || $col === 'Account') {
        $accountColumn = $col;
    }
}

// ستون‌های پیش‌فرض اگر پیدا نشدند
if (!$customerCodeColumn) {
    foreach (['KodM', 'CustomerCode', 'کد_مشتری', 'مشتری'] as $col) {
        if (in_array($col, $columnNames)) {
            $customerCodeColumn = $col;
            break;
        }
    }
}

if (!$purchasePriceColumn && in_array('KHarid', $columnNames)) {
    $purchasePriceColumn = 'KHarid';
}

if (!$salePriceColumn && in_array('Froush', $columnNames)) {
    $salePriceColumn = 'Froush';
}

if (!$dateColumn && in_array('Tarikh', $columnNames)) {
    $dateColumn = 'Tarikh';
}

// گرفتن تمام مشتریان برای جستجوی نام
$allCustomers = [];
try {
    $customerStmt = $dbCustomers->query("SELECT Kod, NameM FROM VpnUser");
    $allCustomers = $customerStmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $allCustomers = [];
}

// ========== توابع کمکی برای تاریخ ==========
function normalizePersianDateForComparison($date) {
    if (empty($date)) return '';
    
    // حذف فاصله و نویسه‌های اضافی
    $date = trim($date);
    
    // تبدیل جداکننده‌ها به خط تیره
    $date = str_replace(['/', '.', 'ـ'], '-', $date);
    
    // حذف نویسه‌های غیرعددی و خط تیره
    $date = preg_replace('/[^\d\-]/', '', $date);
    
    // اطمینان از فرمت YYYY-MM-DD
    $parts = explode('-', $date);
    if (count($parts) === 3) {
        $year = $parts[0];
        $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
        $day = str_pad($parts[2], 2, '0', STR_PAD_LEFT);
        return "$year-$month-$day";
    }
    
    return $date;
}

// ========== فیلترهای سرور-ساید ==========
$filters = [
    'search' => $_GET['search'] ?? '',
    'search_mode' => $_GET['search_mode'] ?? 'all',
    'customer_id' => $_GET['customer_id'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

// اگر مشتری خاصی انتخاب شده، نامش را پیدا کن
$selectedCustomerName = '';
if (!empty($filters['customer_id'])) {
    foreach ($allCustomers as $cust) {
        if ($cust['Kod'] == $filters['customer_id']) {
            $selectedCustomerName = $cust['NameM'];
            break;
        }
    }
}

// ========== گرفتن اطلاعات فروش با فیلترها ==========
$whereConditions = [];
$params = [];

// فیلتر جستجو
if (!empty($filters['search'])) {
    if ($filters['search_mode'] === 'code') {
        $whereConditions[] = "Kod = ?";
        $params[] = $filters['search'];
    } 
    elseif ($filters['search_mode'] === 'customer_code' && $customerCodeColumn) {
        $whereConditions[] = "$customerCodeColumn = ?";
        $params[] = $filters['search'];
    }
    elseif ($filters['search_mode'] === 'customer_name' && !empty($filters['customer_id'])) {
        $whereConditions[] = "$customerCodeColumn = ?";
        $params[] = $filters['customer_id'];
    }
    elseif ($filters['search_mode'] !== 'all' && in_array($filters['search_mode'], $columnNames)) {
        $whereConditions[] = $filters['search_mode'] . " LIKE ?";
        $params[] = '%' . $filters['search'] . '%';
    }
    elseif ($filters['search_mode'] === 'all') {
        $searchConditions = [];
        foreach ($columnNames as $col) {
            $searchConditions[] = "$col LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }
        $whereConditions[] = "(" . implode(" OR ", $searchConditions) . ")";
    }
}

// فیلتر مشتری
if (!empty($filters['customer_id']) && $customerCodeColumn) {
    $whereConditions[] = "$customerCodeColumn = ?";
    $params[] = $filters['customer_id'];
}

// فیلتر تاریخ (با فرمت فارسی)
// ========== فیلتر تاریخ (با فرمت فارسی) - نسخه اصلاح شده ==========
if (!empty($filters['date_from']) && $dateColumn) {
    $normalizedFrom = normalizePersianDateForComparison($filters['date_from']);
    if (!empty($normalizedFrom)) {
        $whereConditions[] = "$dateColumn >= ?";
        $params[] = $normalizedFrom;
    }
}

if (!empty($filters['date_to']) && $dateColumn) {
    $normalizedTo = normalizePersianDateForComparison($filters['date_to']);
    if (!empty($normalizedTo)) {
        $whereConditions[] = "$dateColumn <= ?";
        $params[] = $normalizedTo;
    }
}

// ساخت کوئری نهایی
$sql = "SELECT * FROM VpnHesab";
if (!empty($whereConditions)) {
    $sql .= " WHERE " . implode(" AND ", $whereConditions);
}
$sql .= " ORDER BY Kod DESC";

// گرفتن اطلاعات فروش
$stmt = $dbSales->prepare($sql);
$stmt->execute($params);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ========== محاسبه جمع‌های کل ==========
$total_purchase = 0;
$total_sale = 0;
$total_profit = 0;

foreach ($sales as $sale) {
    // محاسبه خرید
    if ($purchasePriceColumn && isset($sale[$purchasePriceColumn])) {
        $purchase_value = $sale[$purchasePriceColumn];
        // حذف کاراکترهای غیرعددی
        $purchase_value = preg_replace('/[^\d]/', '', $purchase_value);
        $total_purchase += floatval($purchase_value);
    }
    
    // محاسبه فروش
    if ($salePriceColumn && isset($sale[$salePriceColumn])) {
        $sale_value = $sale[$salePriceColumn];
        // حذف کاراکترهای غیرعددی
        $sale_value = preg_replace('/[^\d]/', '', $sale_value);
        $total_sale += floatval($sale_value);
    }
}

$total_profit = $total_sale - $total_purchase;

// ========== ترجمه نام ستون‌ها ==========
function translateColumnName($name) {
    $translations = [
        'Kod' => 'کد فروش',
        'KodM' => 'کد مشتری',
        'CustomerCode' => 'کد مشتری',
        'KHarid' => 'قیمت خرید',
        'Froush' => 'قیمت فروش',
        'Mablagh' => 'مبلغ',
        'Tarikh' => 'تاریخ',
        'Vaziat' => 'وضعیت',
        'UserA' => 'یوزر A',
        'Account' => 'اکانت',
        'NameM' => 'نام مشتری',
        'SHomare' => 'شماره',
        'Tozihat' => 'توضیحات',
        'Moaref' => 'معرف',
        'کد' => 'کد',
        'مشتری' => 'کد مشتری',
        'خرید' => 'قیمت خرید',
        'فروش' => 'قیمت فروش',
        'تاریخ' => 'تاریخ',
        'وضعیت' => 'وضعیت',
        'usera' => 'یوزر A',
        'account' => 'اکانت',
    ];
    
    return $translations[$name] ?? $name;
}

// ستون‌هایی که باید نمایش داده شوند
$displayColumns = $columnNames;

// اگر قیمت خرید مخفی است
if ($settings['hide_purchase_price'] && $purchasePriceColumn) {
    $key = array_search($purchasePriceColumn, $displayColumns);
    if ($key !== false) {
        unset($displayColumns[$key]);
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>فروش</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="wrapper">

<div class="sidebar">
    <h2><i class="fas fa-sliders-h"></i> پنل مدیریت</h2>
    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> داشبورد</a>
    <a href="customers.php"><i class="fas fa-users"></i> مشتریان</a>
    <a href="sales.php" class="active"><i class="fas fa-chart-line"></i> فروش</a>
    <a href="settings.php"><i class="fas fa-cog"></i> تنظیمات</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> خروج</a>
</div>

<div class="content">
    <h2>
        <i class="fas fa-chart-line"></i> لیست فروش‌ها 
        <small style="color: #94a3b8; font-size: 0.9rem;">
            (<?= count($sales) ?> رکورد)
        </small>
    </h2>

    <!-- کارت‌های جمع کل -->
    <div class="cards" style="margin-bottom: 30px; grid-template-columns: repeat(3, 1fr);">
        <div class="card">
            <i class="fas fa-shopping-cart" style="font-size: 2rem; color: #f59e0b;"></i>
            <div style="margin-top: 10px; font-size: 1rem;">کل خرید</div>
            <b id="totalPurchaseDisplay"><?= number_format($total_purchase) ?></b>
            <div style="margin-top: 5px; font-size: 0.8rem; color: #94a3b8;">تومان</div>
        </div>
        
        <div class="card">
            <i class="fas fa-cash-register" style="font-size: 2rem; color: #10b981;"></i>
            <div style="margin-top: 10px; font-size: 1rem;">کل فروش</div>
            <b id="totalSaleDisplay"><?= number_format($total_sale) ?></b>
            <div style="margin-top: 5px; font-size: 0.8rem; color: #94a3b8;">تومان</div>
        </div>
        
        <div class="card">
            <i class="fas fa-chart-line" style="font-size: 2rem; color: <?= $total_profit >= 0 ? '#10b981' : '#ef4444' ?>;"></i>
            <div style="margin-top: 10px; font-size: 1rem;">کل سود</div>
            <b id="totalProfitDisplay" style="color: <?= $total_profit >= 0 ? '#10b981' : '#ef4444' ?>">
                <?= number_format($total_profit) ?>
            </b>
            <div style="margin-top: 5px; font-size: 0.8rem; color: #94a3b8;">تومان</div>
        </div>
    </div>

    <!-- فیلترهای پیشرفته -->
    <div class="filters-container">
        <form method="get" action="sales.php" id="filterForm" class="filters-form">
            <div class="filter-row">
                <!-- جستجوی عمومی -->
                <div class="filter-group" style="flex: 2;">
                    <label><i class="fas fa-search"></i> جستجوی فوری</label>
                    <div style="display: flex; gap: 10px;">
                        <select name="search_mode" id="searchModeSelect" style="width: 250px;">
                            <option value="all" <?= $filters['search_mode'] === 'all' ? 'selected' : '' ?>>همه فیلدها</option>
                            <option value="code" <?= $filters['search_mode'] === 'code' ? 'selected' : '' ?>>کد فروش (دقیق)</option>
                            <?php if ($customerCodeColumn): ?>
                            <option value="customer_code" <?= $filters['search_mode'] === 'customer_code' ? 'selected' : '' ?>>
                                کد مشتری (دقیق)
                            </option>
                            <option value="customer_name" <?= $filters['search_mode'] === 'customer_name' ? 'selected' : '' ?>>
                                نام مشتری
                            </option>
                            <?php endif; ?>
                            <?php if ($userAColumn): ?>
                            <option value="<?= $userAColumn ?>" <?= $filters['search_mode'] === $userAColumn ? 'selected' : '' ?>>
                                <?= translateColumnName($userAColumn) ?>
                            </option>
                            <?php endif; ?>
                            <?php if ($accountColumn): ?>
                            <option value="<?= $accountColumn ?>" <?= $filters['search_mode'] === $accountColumn ? 'selected' : '' ?>>
                                <?= translateColumnName($accountColumn) ?>
                            </option>
                            <?php endif; ?>
                        </select>
                        <div style="flex: 1; position: relative;">
                            <input type="text" name="search" id="mainSearchInput" placeholder="متن جستجو..." 
                                   value="<?= htmlspecialchars($filters['search']) ?>" style="width: 100%;">
                            <div id="customerDropdown" class="customer-dropdown" style="display: none;"></div>
                        </div>
                        <input type="hidden" name="customer_id" id="customerIdInput" value="<?= htmlspecialchars($filters['customer_id']) ?>">
                    </div>
                </div>
                
                <!-- فیلتر تاریخ با تقویم حرفه‌ای -->
                <div class="filter-group" style="flex: 2;">
                    <label><i class="fas fa-calendar-alt"></i> فیلتر تاریخ</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <div style="flex: 1; position: relative;">
                            <input type="text" name="date_from" id="dateFrom" 
                                   placeholder="از تاریخ" class="date-input"
                                   value="<?= htmlspecialchars($filters['date_from']) ?>" autocomplete="off" readonly>
                            <div class="calendar" id="calendarFrom"></div>
                        </div>
                        <span>تا</span>
                        <div style="flex: 1; position: relative;">
                            <input type="text" name="date_to" id="dateTo" 
                                   placeholder="تا تاریخ" class="date-input"
                                   value="<?= htmlspecialchars($filters['date_to']) ?>" autocomplete="off" readonly>
                            <div class="calendar" id="calendarTo"></div>
                        </div>
                    </div>
                </div>
                
                <!-- دکمه‌های فیلتر -->
                <div class="filter-group" style="flex: 1; display: flex; gap: 10px; align-items: flex-end;">
                    <button type="submit" class="filter-btn apply-btn">
                        <i class="fas fa-filter"></i> اعمال فیلتر
                    </button>
                    <a href="sales.php" class="filter-btn clear-btn">
                        <i class="fas fa-times"></i> حذف فیلترها
                    </a>
                </div>
            </div>
            
            <!-- نمایش فیلترهای فعال -->
            <?php if (!empty($filters['search']) || !empty($filters['customer_id']) || 
                      !empty($filters['date_from']) || !empty($filters['date_to'])): ?>
            <div class="active-filters">
                <strong>فیلترهای فعال:</strong>
                <?php if (!empty($filters['search'])): ?>
                <span class="filter-tag">
                    <?php 
                    $modeLabel = '';
                    switch($filters['search_mode']) {
                        case 'code': $modeLabel = 'کد فروش'; break;
                        case 'customer_code': $modeLabel = 'کد مشتری'; break;
                        case 'customer_name': $modeLabel = 'نام مشتری'; break;
                        case 'all': $modeLabel = 'همه فیلدها'; break;
                        default: $modeLabel = translateColumnName($filters['search_mode']);
                    }
                    ?>
                    <?= $modeLabel ?>: <?= htmlspecialchars($filters['search']) ?>
                    <a href="sales.php?<?= http_build_query(array_diff_key($filters, ['search' => ''])) ?>">
                        <i class="fas fa-times"></i>
                    </a>
                </span>
                <?php endif; ?>
                <?php if (!empty($selectedCustomerName)): ?>
                <span class="filter-tag">
                    مشتری: <?= htmlspecialchars($selectedCustomerName) ?>
                    <a href="sales.php?<?= http_build_query(array_diff_key($filters, ['customer_id' => ''])) ?>">
                        <i class="fas fa-times"></i>
                    </a>
                </span>
                <?php endif; ?>
                <?php if (!empty($filters['date_from'])): ?>
                <span class="filter-tag">
                    از: <?= htmlspecialchars($filters['date_from']) ?>
                    <a href="sales.php?<?= http_build_query(array_diff_key($filters, ['date_from' => ''])) ?>">
                        <i class="fas fa-times"></i>
                    </a>
                </span>
                <?php endif; ?>
                <?php if (!empty($filters['date_to'])): ?>
                <span class="filter-tag">
                    تا: <?= htmlspecialchars($filters['date_to']) ?>
                    <a href="sales.php?<?= http_build_query(array_diff_key($filters, ['date_to' => ''])) ?>">
                        <i class="fas fa-times"></i>
                    </a>
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- جدول فروش‌ها -->
    <div class="table-box">
        <table id="salesTable" style="width: 100%;">
            <thead>
                <tr>
                    <?php foreach ($displayColumns as $col): ?>
                    <th data-field="<?= $col ?>"><?= translateColumnName($col) ?></th>
                    <?php endforeach; ?>
                    
                    <!-- ستون نام مشتری اگر فعال باشد -->
                    <?php if ($settings['show_customer_name'] && $customerCodeColumn): ?>
                    <th data-field="customer_name">نام مشتری</th>
                    <?php endif; ?>
                    
                    <!-- ستون سود اگر قیمت خرید و فروش داریم -->
                    <?php if ($purchasePriceColumn && $salePriceColumn): ?>
                    <th data-field="profit">سود</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody id="salesTableBody">
            <?php if (count($sales) > 0): ?>
                <?php foreach ($sales as $s): 
                    // محاسبه سود
                    $purchase_price = 0;
                    $sale_price = 0;
                    
                    if ($purchasePriceColumn && isset($s[$purchasePriceColumn])) {
                        $purchase_price = preg_replace('/[^\d]/', '', $s[$purchasePriceColumn]);
                        $purchase_price = floatval($purchase_price);
                    }
                    
                    if ($salePriceColumn && isset($s[$salePriceColumn])) {
                        $sale_price = preg_replace('/[^\d]/', '', $s[$salePriceColumn]);
                        $sale_price = floatval($sale_price);
                    }
                    
                    $profit = $sale_price - $purchase_price;
                    
                    // پیدا کردن نام مشتری
                    $customer_code = $customerCodeColumn ? ($s[$customerCodeColumn] ?? '') : '';
                    $customer_name = '';
                    foreach ($allCustomers as $cust) {
                        if ($cust['Kod'] == $customer_code) {
                            $customer_name = $cust['NameM'];
                            break;
                        }
                    }
                    if (empty($customer_name)) $customer_name = 'نامشخص';
                    
                    // وضعیت
                    $status = $statusColumn ? ($s[$statusColumn] ?? 'تکمیل شده') : 'تکمیل شده';
                    $statusClass = ($status === 'تکمیل شده' || $status === 'موفق') ? 'success' : 'warning';
                    
                    // تاریخ
                    $sale_date = $dateColumn ? ($s[$dateColumn] ?? '') : '';
                    $normalized_date = normalizePersianDateForComparison($sale_date);
                ?>
                <tr data-raw='<?= json_encode($s, JSON_UNESCAPED_UNICODE) ?>'
                    data-customer-id="<?= htmlspecialchars($customer_code) ?>"
                    data-customer-name="<?= htmlspecialchars($customer_name) ?>"
                    data-purchase="<?= $purchase_price ?>"
                    data-sale="<?= $sale_price ?>"
                    data-profit="<?= $profit ?>"
                    data-date="<?= htmlspecialchars($normalized_date) ?>"
                    data-original-date="<?= htmlspecialchars($sale_date) ?>">
                    <?php foreach ($displayColumns as $col): 
                        $value = $s[$col] ?? '';
                        $displayValue = htmlspecialchars($value);
                        
                        // فرمت کردن مقادیر خاص
                        if ($col === $purchasePriceColumn && is_numeric($purchase_price) && $purchase_price > 0) {
                            $displayValue = number_format($purchase_price) . ' تومان';
                        }
                        
                        if ($col === $salePriceColumn && is_numeric($sale_price) && $sale_price > 0) {
                            $displayValue = number_format($sale_price) . ' تومان';
                        }
                        
                        // وضعیت
                        if ($col === $statusColumn) {
                            $displayValue = '<span class="status-badge ' . $statusClass . '">' . $displayValue . '</span>';
                        }
                    ?>
                    <td data-field="<?= $col ?>"><?= $displayValue ?></td>
                    <?php endforeach; ?>
                    
                    <!-- ستون نام مشتری -->
                    <?php if ($settings['show_customer_name'] && $customerCodeColumn): ?>
                    <td data-field="customer_name">
                        <div class="customer-name"><?= htmlspecialchars($customer_name) ?></div>
                        <?php if ($customer_code): ?>
                        <div class="customer-code">کد: <?= htmlspecialchars($customer_code) ?></div>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    
                    <!-- ستون سود -->
                    <?php if ($purchasePriceColumn && $salePriceColumn): ?>
                    <td data-field="profit" style="color: <?= $profit >= 0 ? '#10b981' : '#ef4444' ?>; font-weight: bold;">
                        <?= number_format($profit) ?> تومان
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr id="noResultsRow">
                    <td colspan="<?= count($displayColumns) + ($settings['show_customer_name'] ? 1 : 0) + ($purchasePriceColumn && $salePriceColumn ? 1 : 0) ?>" 
                        style="text-align: center; padding: 40px; color: #94a3b8;">
                        <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 15px; display: block; opacity: 0.5;"></i>
                        <?php if (!empty($filters['search']) || !empty($filters['customer_id']) || 
                                  !empty($filters['date_from']) || !empty($filters['date_to'])): ?>
                        هیچ رکوردی با فیلترهای انتخابی مطابقت ندارد
                        <?php else: ?>
                        هیچ رکورد فروشی یافت نشد
                        <?php endif; ?>
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
// داده‌های مشتریان
const customers = <?= json_encode($allCustomers, JSON_UNESCAPED_UNICODE) ?>;
let currentFilterMode = '<?= $filters["search_mode"] ?>';
let currentCustomerId = '<?= $filters["customer_id"] ?>';

// ========== تابع تبدیل میلادی به شمسی ==========
function g2j(gy, gm, gd){
    var d=[0,31,59,90,120,151,181,212,243,273,304,334];
    var jy=(gy<=1600)?0:979; gy-=(gy<=1600)?621:1600;
    var gy2=(gm>2)?gy+1:gy;
    var days=365*gy+Math.floor((gy2+3)/4)-Math.floor((gy2+99)/100)+Math.floor((gy2+399)/400)-80+gd+d[gm-1];
    jy+=33*Math.floor(days/12053); days%=12053;
    jy+=4*Math.floor(days/1461); days%=1461;
    if(days>365){jy+=Math.floor((days-1)/365); days=(days-1)%365;}
    var jm=(days<186)?1+Math.floor(days/31):7+Math.floor((days-186)/30);
    var jd=1+((days<186)?days%31:(days-186)%30);
    return [jy,jm,jd];
}

// ========== تابع ایجاد تقویم ==========
function createCalendar(containerId, inputId) {
    const input = document.getElementById(inputId);
    const container = document.getElementById(containerId);
    
    const months = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
    
    let mode = 'day';
    let year, month, day;
    
    // تاریخ امروز
    function getToday(){
        const now = new Date();
        return g2j(now.getFullYear(), now.getMonth()+1, now.getDate());
    }
    
    let today = getToday();
    year = today[0]; month = today[1]; day = today[2];
    
    // اگر input قبلاً مقدار دارد، آن را تجزیه کن
    if (input.value) {
        const parts = input.value.split(/[\/\-]/);
        if (parts.length === 3) {
            year = parseInt(parts[0]);
            month = parseInt(parts[1]);
            day = parseInt(parts[2]);
        }
    }
    
    // بروزرسانی متن بالا
    function updateHeader() {
        const header = container.querySelector('.cal-header');
        header.querySelector('#dayText').innerText = day ? day : 'روز';
        header.querySelector('#monthText').innerText = month ? months[month-1] : 'ماه';
        header.querySelector('#yearText').innerText = year;
    }
    
    // نمایش روزها
    function renderDays() {
        mode = 'day';
        updateHeader();
        const content = container.querySelector('#content');
        content.className = 'grid days';
        content.innerHTML = '';
        
        // محاسبه تعداد روزهای ماه
        let totalDays;
        if (month <= 6) {
            totalDays = 31;
        } else if (month <= 11) {
            totalDays = 30;
        } else {
            // اسفند - بررسی سال کبیسه
            totalDays = ((year % 33) % 4 === 1) ? 30 : 29;
        }
        
        // روزهای هفته
        const weekDays = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];
        weekDays.forEach(day => {
            content.innerHTML += `<div class="item week-day">${day}</div>`;
        });
        
        // روزهای ماه
        for(let d = 1; d <= totalDays; d++) {
            let cls = (d === today[2] && month === today[1] && year === today[0]) ? 'item today' : 'item';
            content.innerHTML += `<div class="${cls}" onclick="selectDay('${containerId}', ${d}, event)">${d}</div>`;
        }
    }
    
    // نمایش ماه‌ها
    function renderMonths() {
        mode = 'month';
        updateHeader();
        const content = container.querySelector('#content');
        content.className = 'grid';
        content.innerHTML = '';
        
        months.forEach((m, i) => {
            let cls = (i+1 === today[1] && year === today[0]) ? 'item today' : 'item';
            content.innerHTML += `<div class="${cls}" onclick="selectMonth('${containerId}', ${i+1}, event)">${m}</div>`;
        });
    }
    
    // نمایش سال‌ها
    function renderYears() {
        mode = 'year';
        updateHeader();
        const content = container.querySelector('#content');
        content.className = 'grid';
        content.innerHTML = '';
        
        for(let y = year-6; y <= year+5; y++) {
            let cls = (y === today[0]) ? 'item today' : 'item';
            content.innerHTML += `<div class="${cls}" onclick="selectYear('${containerId}', ${y}, event)">${y}</div>`;
        }
    }
    
    // ایجاد HTML تقویم
    container.innerHTML = `
        <div class="cal-header">
            <span onclick="yearPrev('${containerId}', event)">➡</span>
            <span onclick="setToday('${containerId}', event)">تاریخ امروز</span>
            <span id="dayText">روز</span>
            <span id="monthText">ماه</span>
            <span id="yearText">سال</span>
            <span onclick="yearNext('${containerId}', event)">⬅</span>
        </div>
        <div id="content" class="grid"></div>
    `;
    
    // اضافه کردن event listeners به هدر
    setTimeout(() => {
        container.querySelector('#dayText').onclick = (e) => { e.stopPropagation(); renderDays(); };
        container.querySelector('#monthText').onclick = (e) => { e.stopPropagation(); renderMonths(); };
        container.querySelector('#yearText').onclick = (e) => { e.stopPropagation(); renderYears(); };
    }, 100);
    
    renderDays();
    
    return {
        year, month, day,
        renderDays, renderMonths, renderYears,
        updateHeader
    };
}

// متغیرهای تقویم
let calendarFrom, calendarTo;

// ========== توابع عمومی برای تقویم ==========
function selectDay(calendarId, d, e) { 
    e.stopPropagation(); 
    const calendar = (calendarId === 'calendarFrom') ? calendarFrom : calendarTo;
    const input = (calendarId === 'calendarFrom') ? document.getElementById('dateFrom') : document.getElementById('dateTo');
    
    calendar.day = d;
    calendar.updateHeader(); 
    input.value = `${calendar.year}/${String(calendar.month).padStart(2,'0')}/${String(calendar.day).padStart(2,'0')}`;
    document.getElementById(calendarId).style.display = 'none'; 
}

function selectMonth(calendarId, m, e) { 
    e.stopPropagation(); 
    const calendar = (calendarId === 'calendarFrom') ? calendarFrom : calendarTo;
    calendar.month = m; 
    calendar.renderDays(); 
    calendar.updateHeader(); 
}

function selectYear(calendarId, y, e) { 
    e.stopPropagation(); 
    const calendar = (calendarId === 'calendarFrom') ? calendarFrom : calendarTo;
    calendar.year = y; 
    calendar.renderMonths(); 
    calendar.updateHeader(); 
}

function setToday(calendarId, e) { 
    e.stopPropagation(); 
    const now = new Date();
    const today = g2j(now.getFullYear(), now.getMonth()+1, now.getDate());
    const calendar = (calendarId === 'calendarFrom') ? calendarFrom : calendarTo;
    const input = (calendarId === 'calendarFrom') ? document.getElementById('dateFrom') : document.getElementById('dateTo');
    
    calendar.year = today[0]; 
    calendar.month = today[1]; 
    calendar.day = today[2]; 
    input.value = `${today[0]}/${String(today[1]).padStart(2,'0')}/${String(today[2]).padStart(2,'0')}`;
    calendar.renderDays();
    document.getElementById(calendarId).style.display = 'none';
}

function yearPrev(calendarId, e) { 
    e.stopPropagation(); 
    const calendar = (calendarId === 'calendarFrom') ? calendarFrom : calendarTo;
    calendar.year++; 
    if (calendar.mode === 'year') calendar.renderYears(); 
    else calendar.updateHeader(); 
}

function yearNext(calendarId, e) { 
    e.stopPropagation(); 
    const calendar = (calendarId === 'calendarFrom') ? calendarFrom : calendarTo;
    calendar.year--; 
    if (calendar.mode === 'year') calendar.renderYears(); 
    else calendar.updateHeader(); 
}

document.addEventListener('DOMContentLoaded', function() {
    // فعال کردن کلیک روی ردیف‌ها
    if (typeof setupTableRowClicks !== 'undefined') {
        setupTableRowClicks('salesTable', true);
    }
    
    const searchModeSelect = document.getElementById('searchModeSelect');
    const mainSearchInput = document.getElementById('mainSearchInput');
    const customerDropdown = document.getElementById('customerDropdown');
    const customerIdInput = document.getElementById('customerIdInput');
    const salesTableBody = document.getElementById('salesTableBody');
    const noResultsRow = document.getElementById('noResultsRow');
    
    // ========== ایجاد تقویم‌ها ==========
    calendarFrom = createCalendar('calendarFrom', 'dateFrom');
    calendarTo = createCalendar('calendarTo', 'dateTo');
    
    // تنظیم display برای تقویم‌ها
    document.getElementById('calendarFrom').style.display = 'none';
    document.getElementById('calendarTo').style.display = 'none';
    
    // باز کردن تقویم با کلیک روی input
    document.getElementById('dateFrom').addEventListener('click', function(e) {
        e.stopPropagation();
        document.getElementById('calendarTo').style.display = 'none';
        document.getElementById('calendarFrom').style.display = 'block';
        // موقعیت‌یابی
        const rect = this.getBoundingClientRect();
        const calendar = document.getElementById('calendarFrom');
        calendar.style.top = (rect.bottom + window.scrollY) + 'px';
        calendar.style.left = (rect.left + window.scrollX) + 'px';
    });
    
    document.getElementById('dateTo').addEventListener('click', function(e) {
        e.stopPropagation();
        document.getElementById('calendarFrom').style.display = 'none';
        document.getElementById('calendarTo').style.display = 'block';
        // موقعیت‌یابی
        const rect = this.getBoundingClientRect();
        const calendar = document.getElementById('calendarTo');
        calendar.style.top = (rect.bottom + window.scrollY) + 'px';
        calendar.style.left = (rect.left + window.scrollX) + 'px';
    });
    
    // بستن تقویم با کلیک بیرون
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.calendar') && !e.target.classList.contains('date-input')) {
            document.getElementById('calendarFrom').style.display = 'none';
            document.getElementById('calendarTo').style.display = 'none';
        }
    });
    
    // ذخیره ردیف‌های اصلی برای فیلتر لحظه‌ای
    let originalRows = [];
    if (salesTableBody) {
        originalRows = Array.from(salesTableBody.querySelectorAll('tr')).filter(tr => tr.id !== 'noResultsRow');
    }
    
    // تابع نمایش dropdown مشتریان
    function showCustomerDropdown(searchTerm = '') {
        if (!customerDropdown) return;
        
        const filteredCustomers = customers.filter(customer => 
            customer.NameM.toLowerCase().includes(searchTerm.toLowerCase()) ||
            customer.Kod.toString().includes(searchTerm)
        );
        
        if (filteredCustomers.length === 0) {
            customerDropdown.style.display = 'none';
            return;
        }
        
        customerDropdown.innerHTML = '';
        customerDropdown.style.display = 'block';
        customerDropdown.style.position = 'absolute';
        customerDropdown.style.top = '100%';
        customerDropdown.style.left = '0';
        customerDropdown.style.right = '0';
        customerDropdown.style.background = '#1e293b';
        customerDropdown.style.border = '1px solid #334155';
        customerDropdown.style.borderRadius = '8px';
        customerDropdown.style.maxHeight = '200px';
        customerDropdown.style.overflowY = 'auto';
        customerDropdown.style.zIndex = '1000';
        customerDropdown.style.boxShadow = '0 5px 15px rgba(0,0,0,0.3)';
        
        filteredCustomers.forEach(customer => {
            const item = document.createElement('div');
            item.className = 'customer-dropdown-item';
            item.style.cssText = `
                padding: 10px 15px;
                cursor: pointer;
                border-bottom: 1px solid #334155;
                transition: background 0.3s;
            `;
            item.innerHTML = `
                <div style="font-weight: bold;">${customer.NameM}</div>
                <div style="font-size: 0.8rem; color: #94a3b8;">کد: ${customer.Kod}</div>
            `;
            
            item.addEventListener('click', function() {
                mainSearchInput.value = customer.NameM;
                customerIdInput.value = customer.Kod;
                customerDropdown.style.display = 'none';
                currentCustomerId = customer.Kod;
                
                // ارسال فرم برای فیلتر سرور-ساید
                document.getElementById('filterForm').submit();
            });
            
            item.addEventListener('mouseenter', function() {
                this.style.background = '#334155';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.background = '';
            });
            
            customerDropdown.appendChild(item);
        });
    }
    
    // مخفی کردن dropdown با کلیک بیرون
    document.addEventListener('click', function(e) {
        if (customerDropdown && 
            !mainSearchInput.contains(e.target) && 
            !customerDropdown.contains(e.target)) {
            customerDropdown.style.display = 'none';
        }
    });
    
    // تابع فیلتر لحظه‌ای
    function applyLiveFilter() {
        const mode = searchModeSelect.value;
        const searchTerm = mainSearchInput.value.trim().toLowerCase();
        
        // اگر حالت نام مشتری است، از فیلتر لحظه‌ای استفاده نکن
        if (mode === 'customer_name') {
            return;
        }
        
        let visibleCount = 0;
        let totalPurchase = 0;
        let totalSale = 0;
        let totalProfit = 0;
        
        // اگر جستجو خالی است، همه ردیف‌ها را نشان بده
        if (searchTerm === '') {
            originalRows.forEach(row => {
                row.style.display = '';
                visibleCount++;
                
                // محاسبه جمع‌ها
                totalPurchase += parseFloat(row.getAttribute('data-purchase') || 0);
                totalSale += parseFloat(row.getAttribute('data-sale') || 0);
                totalProfit += parseFloat(row.getAttribute('data-profit') || 0);
            });
        } else {
            // فیلتر ردیف‌ها
            originalRows.forEach(row => {
                let shouldDisplay = false;
                
                if (mode === 'all') {
                    // جستجو در همه فیلدها
                    const text = row.textContent.toLowerCase();
                    shouldDisplay = text.includes(searchTerm);
                } 
                else if (mode === 'code') {
                    // جستجوی دقیق کد فروش
                    const codeCell = row.querySelector('[data-field="Kod"]');
                    if (codeCell) {
                        const cellValue = codeCell.textContent.trim().toLowerCase();
                        shouldDisplay = cellValue === searchTerm;
                    }
                }
                else if (mode === 'customer_code') {
                    // جستجوی دقیق کد مشتری
                    const customerId = row.getAttribute('data-customer-id') || '';
                    shouldDisplay = customerId.toLowerCase() === searchTerm;
                }
                else {
                    // جستجو در فیلد خاص
                    const fieldCell = row.querySelector(`[data-field="${mode}"]`);
                    if (fieldCell) {
                        const cellValue = fieldCell.textContent.toLowerCase();
                        shouldDisplay = cellValue.includes(searchTerm);
                    }
                }
                
                row.style.display = shouldDisplay ? '' : 'none';
                
                if (shouldDisplay) {
                    visibleCount++;
                    totalPurchase += parseFloat(row.getAttribute('data-purchase') || 0);
                    totalSale += parseFloat(row.getAttribute('data-sale') || 0);
                    totalProfit += parseFloat(row.getAttribute('data-profit') || 0);
                }
            });
        }
        
        // آپدیت نمایش تعداد
        const title = document.querySelector('.content h2 small');
        if (title) {
            title.textContent = `(${visibleCount} رکورد)`;
        }
        
        // آپدیت جمع‌های کل
        updateTotalDisplays(totalPurchase, totalSale, totalProfit);
        
        // نمایش/مخفی کردن ردیف "هیچ موردی یافت نشد"
        if (noResultsRow) {
            if (visibleCount === 0 && searchTerm !== '') {
                noResultsRow.style.display = '';
                noResultsRow.querySelector('td').textContent = 'هیچ رکوردی با این جستجو مطابقت ندارد';
            } else {
                noResultsRow.style.display = 'none';
            }
        }
    }
    
    // تابع آپدیت نمایش جمع‌ها
    function updateTotalDisplays(purchase, sale, profit) {
        document.getElementById('totalPurchaseDisplay').textContent = Math.round(purchase).toLocaleString();
        document.getElementById('totalSaleDisplay').textContent = Math.round(sale).toLocaleString();
        
        const profitElement = document.getElementById('totalProfitDisplay');
        profitElement.textContent = Math.round(profit).toLocaleString();
        profitElement.style.color = profit >= 0 ? '#10b981' : '#ef4444';
        
        // آیکون سود
        const profitIcon = document.querySelector('.card:nth-child(3) i');
        if (profitIcon) {
            profitIcon.style.color = profit >= 0 ? '#10b981' : '#ef4444';
        }
    }
    
    // تغییر نوع جستجو
    searchModeSelect.addEventListener('change', function() {
        const mode = this.value;
        currentFilterMode = mode;
        
        // مخفی کردن dropdown
        if (customerDropdown) {
            customerDropdown.style.display = 'none';
        }
        
        // پاک کردن مقدار قبلی
        mainSearchInput.value = '';
        customerIdInput.value = '';
        currentCustomerId = '';
        
        // تغییر placeholder
        if (mode === 'code') {
            mainSearchInput.placeholder = 'کد فروش را وارد کنید (دقیق) - فیلتر لحظه‌ای';
        } else if (mode === 'customer_code') {
            mainSearchInput.placeholder = 'کد مشتری را وارد کنید (دقیق) - فیلتر لحظه‌ای';
        } else if (mode === 'customer_name') {
            mainSearchInput.placeholder = 'نام مشتری را وارد کنید... (لیست باز می‌شود)';
        } else if (mode === 'all') {
            mainSearchInput.placeholder = 'در همه فیلدها جستجو کنید... - فیلتر لحظه‌ای';
        } else {
            mainSearchInput.placeholder = 'متن جستجو... - فیلتر لحظه‌ای';
        }
        
        // بازنشانی نمایش
        originalRows.forEach(row => row.style.display = '');
        if (noResultsRow) noResultsRow.style.display = 'none';
        
        // آپدیت تعداد
        const title = document.querySelector('.content h2 small');
        if (title) {
            title.textContent = `(${originalRows.length} رکورد)`;
        }
        
        // بازنشانی جمع‌ها
        let totalP = 0, totalS = 0, totalPr = 0;
        originalRows.forEach(row => {
            totalP += parseFloat(row.getAttribute('data-purchase') || 0);
            totalS += parseFloat(row.getAttribute('data-sale') || 0);
            totalPr += parseFloat(row.getAttribute('data-profit') || 0);
        });
        updateTotalDisplays(totalP, totalS, totalPr);
    });
    
    // جستجوی لحظه‌ای برای همه موارد جز نام مشتری
    mainSearchInput.addEventListener('input', function() {
        const mode = searchModeSelect.value;
        const searchTerm = this.value.trim();
        
        if (mode === 'customer_name') {
            // برای نام مشتری، dropdown نشان بده
            if (searchTerm.length >= 1) {
                showCustomerDropdown(searchTerm);
            } else {
                if (customerDropdown) {
                    customerDropdown.style.display = 'none';
                }
                customerIdInput.value = '';
                currentCustomerId = '';
            }
        } else {
            // برای سایر موارد، dropdown مخفی کن و فیلتر لحظه‌ای اعمال کن
            if (customerDropdown) {
                customerDropdown.style.display = 'none';
            }
            
            // اعمال فیلتر لحظه‌ای
            setTimeout(applyLiveFilter, 100);
        }
    });
    
    // وقتی روی input کلیک می‌شود (برای نام مشتری)
    mainSearchInput.addEventListener('click', function() {
        const mode = searchModeSelect.value;
        const searchTerm = this.value.trim();
        
        if (mode === 'customer_name' && searchTerm.length >= 1) {
            showCustomerDropdown(searchTerm);
        }
    });
    
    // جلوگیری از ارسال فرم با Enter (مگر اینکه dropdown باز باشد)
    document.getElementById('filterForm').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            const dropdown = document.getElementById('customerDropdown');
            if (dropdown && dropdown.style.display === 'block') {
                e.preventDefault();
                return false;
            }
            
            // اگر در حالت نام مشتری هستیم و مقدار داریم، اجازه ارسال بده
            if (searchModeSelect.value === 'customer_name' && currentCustomerId) {
                return true;
            }
            
            // برای سایر موارد، از ارسال فرم جلوگیری کن (چون فیلتر لحظه‌ای است)
            if (searchModeSelect.value !== 'customer_name') {
                e.preventDefault();
                return false;
            }
        }
    });
    
    // اجرای اولیه برای تنظیم placeholder
    searchModeSelect.dispatchEvent(new Event('change'));
});
</script>

<style>
/* استایل فیلترها */
.filters-container {
    background: rgba(15, 23, 42, 0.6);
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 25px;
    border: 1px solid #334155;
}

.filters-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.filter-row {
    display: flex;
    gap: 20px;
    align-items: flex-end;
}

.filter-group {
    flex: 1;
}

.filter-group label {
    display: block;
    margin-bottom: 8px;
    color: #94a3b8;
    font-weight: 500;
}

.filter-group input, .filter-group select {
    width: 100%;
    padding: 12px 15px;
    border-radius: 8px;
    border: 1px solid #475569;
    background: rgba(30, 41, 59, 0.8);
    color: #e2e8f0;
    font-size: 14px;
}

.filter-group input:focus, .filter-group select:focus {
    border-color: #38bdf8;
    outline: none;
    box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.2);
}

.filter-btn {
    padding: 12px 25px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s;
}

.apply-btn {
    background: #38bdf8;
    color: white;
}

.apply-btn:hover {
    background: #0ea5e9;
    transform: translateY(-2px);
}

.clear-btn {
    background: #94a3b8;
    color: white;
}

.clear-btn:hover {
    background: #64748b;
    transform: translateY(-2px);
}

/* استایل برای تقویم حرفه‌ای */
.date-input {
    width: 100%;
    padding: 12px 15px;
    border-radius: 8px;
    border: 1px solid #475569;
    background: rgba(30, 41, 59, 0.8);
    color: #e2e8f0;
    font-size: 14px;
    cursor: pointer;
    position: relative;
}

.date-input:focus {
    border-color: #38bdf8;
    outline: none;
    box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.2);
}

/* استایل تقویم (بر اساس کد شما) */
.calendar {
    width: 380px;
    background: #1e293b;
    border-radius: 10px;
    border: 1px solid #334155;
    padding: 15px;
    display: none;
    position: absolute;
    z-index: 9999;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
}

.cal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #2c3e50;
    color: #fff;
    padding: 8px 10px;
    border-radius: 6px;
    font-size: 13px;
    margin-bottom: 10px;
}

.cal-header span {
    cursor: pointer;
    padding: 4px 8px;
    min-width: 50px;
    text-align: center;
    border-radius: 4px;
    transition: background 0.3s;
}

.cal-header span:hover {
    background: #34495e;
}

.grid {
    display: grid;
    gap: 5px;
    margin-top: 10px;
}

.days {
    grid-template-columns: repeat(7, 1fr);
}

.item {
    background: rgba(30, 41, 59, 0.8);
    padding: 10px;
    text-align: center;
    border-radius: 6px;
    cursor: pointer;
    color: #e2e8f0;
    border: 1px solid transparent;
    transition: all 0.2s;
}

.item:hover {
    background: #38bdf8;
    color: #fff;
    transform: scale(1.05);
}

.today {
    background: #10b981 !important;
    color: white !important;
    font-weight: bold;
}

.week-day {
    background: #475569;
    color: #94a3b8;
    font-weight: bold;
    cursor: default;
}

.week-day:hover {
    background: #475569 !important;
    color: #94a3b8 !important;
    transform: none !important;
}

/* فیلترهای فعال */
.active-filters {
    background: rgba(30, 41, 59, 0.8);
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #475569;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}

.active-filters strong {
    color: #38bdf8;
}

.filter-tag {
    background: rgba(56, 189, 248, 0.2);
    color: #38bdf8;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 5px;
}

.filter-tag a {
    color: #94a3b8;
    text-decoration: none;
}

.filter-tag a:hover {
    color: #ef4444;
}

/* dropdown مشتریان */
.customer-dropdown-item:hover {
    background: #334155 !important;
}

/* واکنش‌گرا */
@media (max-width: 1024px) {
    .filter-row {
        flex-direction: column;
    }
    
    .filter-group {
        width: 100%;
    }
    
    .calendar {
        width: 320px;
        left: 0 !important;
        right: 0 !important;
        margin: 0 auto;
    }
}
</style>

</body>
</html>