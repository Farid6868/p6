/* ===== Search Function ===== */
function advancedLiveSearch(inputId, tableId, modeSelectId = null) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    
    // اگر المان‌ها پیدا نشدند، خطا ندهیم
    if (!input || !table) {
        console.error('عنصر جستجو یا جدول پیدا نشد:', inputId, tableId);
        return;
    }
    
    const rows = table.querySelectorAll("tbody tr");
    const modeSelect = modeSelectId ? document.getElementById(modeSelectId) : null;

    // تابع فیلتر
    function filter() {
        const value = input.value.trim().toLowerCase();
        const mode = modeSelect ? modeSelect.value : "all";

        rows.forEach(row => {
            let text = "";
            let shouldDisplay = false;

            if (mode === "all") {
                // جستجو در همه ستون‌ها
                const cells = row.querySelectorAll('td');
                cells.forEach(cell => {
                    text += cell.textContent.toLowerCase() + " ";
                });
                shouldDisplay = text.includes(value);
            } else {
                // جستجو در ستون خاص
                const cell = row.querySelector(`td[data-field="${mode}"]`);
                if (cell) {
                    text = cell.textContent.toLowerCase();

                    // اگر جستجو بر اساس کد باشد، فقط برابر دقیق نمایش بده
                    if (mode === 'code' || mode === 'Kod' || mode === 'کد') {
                        shouldDisplay = text === value.toLowerCase();
                    } else {
                        shouldDisplay = text.includes(value);
                    }
                } else {
                    // اگر ستون با data-field پیدا نشد
                    const selectIndex = getColumnIndexByMode(mode, modeSelectId);
                    if (selectIndex !== -1) {
                        const cells = row.querySelectorAll('td');
                        if (cells[selectIndex]) {
                            text = cells[selectIndex].textContent.toLowerCase();

                            // اگر جستجو بر اساس کد باشد
                            if (modeSelectId && modeSelectId.includes('code') || mode.includes('کد')) {
                                shouldDisplay = text === value.toLowerCase();
                            } else {
                                shouldDisplay = text.includes(value);
                            }
                        }
                    }
                }
            }

            row.style.display = shouldDisplay ? "" : "none";
        });
    }

    // تابع کمکی برای پیدا کردن ایندکس ستون بر اساس mode
    function getColumnIndexByMode(mode, selectId) {
        const select = document.getElementById(selectId);
        if (!select) return -1;
        
        const options = select.options;
        for (let i = 0; i < options.length; i++) {
            if (options[i].value === mode) {
                return i; // ایندکس ستون برابر با ایندکس آپشن است
            }
        }
        return -1;
    }

    // رویدادها
    input.addEventListener("keyup", filter);
    input.addEventListener("search", filter); // برای clear کردن جستجو
    if (modeSelect) {
        modeSelect.addEventListener("change", filter);
    }

    // اجرای اولیه برای پنهان کردن هیچ ردیفی
    filter();
}

/* ===== Modal Functions ===== */
function openModal(title, data) {
    // حذف Modal قبلی اگر وجود دارد
    const oldModal = document.getElementById('detailModal');
    if (oldModal) oldModal.remove();
    
    // ساخت ساختار Modal
    const modalHTML = `
        <div class="modal-overlay" id="detailModal">
            <div class="modal-box">
                <div class="modal-header">
                    <h3>${title}</h3>
                    <button class="modal-close" onclick="closeModal()">×</button>
                </div>
                <div class="modal-body" id="modalContent">
                    ${generateModalContent(data)}
                </div>
            </div>
        </div>
    `;
    
    // اضافه کردن Modal به صفحه
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // نمایش Modal با انیمیشن
    setTimeout(() => {
        const modal = document.getElementById('detailModal');
        modal.style.display = 'flex';
    }, 10);
    
    // بستن با کلید ESC
    const escHandler = function(e) {
        if (e.key === 'Escape') closeModal();
    };
    document.addEventListener('keydown', escHandler);
    
    // حذف event listener بعد از بستن modal
    document.getElementById('detailModal').addEventListener('click', function overlayClick(e) {
        if (e.target.id === 'detailModal') {
            closeModal();
            document.removeEventListener('keydown', escHandler);
        }
    });
}

function generateModalContent(data) {
    let html = '';
    for (const [key, value] of Object.entries(data)) {
        const label = getPersianLabel(key);
        const displayValue = (value === '' || value === null || value === undefined) ? '---' : value;
        html += `
            <div class="modal-row">
                <div class="modal-label">${label}:</div>
                <div class="modal-value">${displayValue}</div>
            </div>
        `;
    }
    
    if (html === '') {
        html = '<div style="text-align: center; color: #94a3b8; padding: 30px;">هیچ اطلاعاتی موجود نیست</div>';
    }
    
    return html;
}

function getPersianLabel(key) {
    const labels = {
        'Kod': 'کد',
        'NameM': 'نام مشتری',
        'SHomare': 'شماره تماس',
        'Tozihat': 'توضیحات',
        'Moaref': 'معرف',
        'Mablagh': 'مبلغ',
        'Tarikh': 'تاریخ',
        'Vaziat': 'وضعیت',
        'CustomerCode': 'کد مشتری',
        'code': 'کد',
        'name': 'نام',
        'phone': 'شماره',
        'desc': 'توضیحات',
        'moaref': 'معرف',
        'customer': 'مشتری',
        'amount': 'مبلغ',
        'date': 'تاریخ',
        'status': 'وضعیت'
    };
    return labels[key] || key;
}

function closeModal() {
    const modal = document.getElementById('detailModal');
    if (modal) {
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.remove();
        }, 300);
    }
}

/* ===== تابع کلیک روی ردیف‌های جدول ===== */
function setupTableRowClicks(tableId, isSales = false) {
    const table = document.getElementById(tableId);
    if (!table) {
        console.warn('جدول پیدا نشد:', tableId);
        return;
    }
    
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        row.classList.add('clickable-row');
        
        row.addEventListener('click', function() {
            let data = {};
            
            // جمع‌آوری داده‌ها از data-raw اگر موجود باشد
            if (this.dataset.raw) {
                try {
                    data = JSON.parse(this.dataset.raw);
                } catch(e) {
                    console.error('خطا در پردازش JSON:', e);
                }
            }
            
            // اگر data-raw نبود، از سلول‌ها جمع‌آوری کن
            if (Object.keys(data).length === 0) {
                const cells = this.querySelectorAll('td');
                cells.forEach(cell => {
                    const field = cell.getAttribute('data-field');
                    if (field) {
                        data[field] = cell.textContent || cell.innerText;
                    }
                });
            }
            
            openModal(isSales ? 'جزئیات فروش' : 'جزئیات مشتری', data);
        });
    });
}

/* ===== تابع کمکی برای debug ===== */
function debugSearch() {
    console.log('Debug Search Functions:');
    console.log('1. advancedLiveSearch function:', typeof advancedLiveSearch);
    console.log('2. All search inputs:', document.querySelectorAll('input[placeholder*="جستجو"]'));
    console.log('3. All tables:', document.querySelectorAll('table'));
}