<!DOCTYPE html>
<html lang="fa">
<head>
<meta charset="UTF-8">
<title>تقویم شمسی حرفه‌ای 3 مرحله‌ای</title>
<style>
body{font-family:tahoma; background:#f4f6f8; padding:40px; direction:rtl;}
.date-input{width:220px;padding:10px;cursor:pointer; position:relative;}
.calendar{width:380px;background:#fff;border-radius:10px;box-shadow:0 4px 15px rgba(0,0,0,.2);padding:10px;display:none;position:absolute; z-index:9999;}
.cal-header{display:flex;justify-content:space-between;align-items:center;background:#2c3e50;color:#fff;padding:6px 8px;border-radius:6px;font-size:13px;}
.cal-header span{cursor:pointer;padding:4px 6px; min-width:50px; text-align:center;}
.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin-top:10px;}
.days{grid-template-columns:repeat(7,1fr);}
.item{background:#ecf0f1;padding:10px;text-align:center;border-radius:6px;cursor:pointer;}
.item:hover{background:#3498db;color:#fff;}
.today{background:#2ecc71;color:#fff !important;}
</style>
</head>
<body>

<div style="position:relative; display:inline-block;">
    <input id="dateInput" class="date-input" readonly placeholder="انتخاب تاریخ">
    <div class="calendar" id="calendar">
        <div class="cal-header">
            <span onclick="yearPrev(event)">➡</span>
            <span onclick="setToday(event)">تاریخ امروز</span>
            <span id="dayText">روز</span>
            <span id="monthText">ماه</span>
            <span id="yearText">سال</span>
            <span onclick="yearNext(event)">⬅</span>
        </div>
        <div id="content" class="grid"></div>
    </div>
</div>

<script>
// تبدیل میلادی به شمسی
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

// متغیرها
const input=document.getElementById('dateInput');
const cal=document.getElementById('calendar');
const content=document.getElementById('content');
const dayText=document.getElementById('dayText');
const monthText=document.getElementById('monthText');
const yearText=document.getElementById('yearText');
const months=['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];

let mode='day'; // day|month|year
let year, month, day;

// تاریخ امروز
function getToday(){
    const now = new Date();
    return g2j(now.getFullYear(), now.getMonth()+1, now.getDate());
}
let today=getToday();
year=today[0]; month=today[1]; day=today[2];

// بروزرسانی متن بالا
function updateHeader(){
    dayText.innerText=(day)?day:'روز';
    monthText.innerText=(month)?months[month-1]:'ماه';
    yearText.innerText=year;
}

// نمایش روزها
function renderDays(){
    mode='day';
    updateHeader();
    content.className='grid days';
    content.innerHTML='';
    let totalDays=(month<=6)?31:(month<=11?30:(year%33%4===1?30:29));
    for(let d=1; d<=totalDays; d++){
        let cls = (d===today[2] && month===today[1] && year===today[0]) ? 'item today' : 'item';
        content.innerHTML+=`<div class="${cls}" onclick="selectDay(${d}, event)">${d}</div>`;
    }
}

// نمایش ماه‌ها
function renderMonths(){
    mode='month';
    updateHeader();
    content.className='grid';
    content.innerHTML='';
    months.forEach((m,i)=>{
        let cls = (i+1===today[1] && year===today[0]) ? 'item today' : 'item';
        content.innerHTML+=`<div class="${cls}" onclick="selectMonth(${i+1}, event)">${m}</div>`;
    });
}

// نمایش سال‌ها
function renderYears(){
    mode='year';
    updateHeader();
    content.className='grid';
    content.innerHTML='';
    for(let y=year-6;y<=year+5;y++){
        let cls = (y===today[0]) ? 'item today' : 'item';
        content.innerHTML+=`<div class="${cls}" onclick="selectYear(${y}, event)">${y}</div>`;
    }
}

// انتخاب‌ها
function selectDay(d, e){ e.stopPropagation(); day=d; updateHeader(); input.value=`${year}/${String(month).padStart(2,'0')}/${String(day).padStart(2,'0')}`; cal.style.display='none'; }
function selectMonth(m, e){ e.stopPropagation(); month=m; renderDays(); updateHeader(); }
function selectYear(y, e){ e.stopPropagation(); year=y; renderMonths(); updateHeader(); }

// بازگشت
function goBack(){
    if(mode==='day') renderMonths();
    else if(mode==='month') renderYears();
}

// تاریخ امروز
function setToday(e){ e.stopPropagation(); let t=getToday(); year=t[0]; month=t[1]; day=t[2]; selectDay(day, e); }

// فلش‌ها (عملکرد معکوس)
function yearPrev(e){ e.stopPropagation(); year++; if(mode==='year') renderYears(); else updateHeader(); } 
function yearNext(e){ e.stopPropagation(); year--; if(mode==='year') renderYears(); else updateHeader(); } 

// کلیک روی روز/ماه/سال در منو برای باز کردن لیست
dayText.onclick=(e)=>{ e.stopPropagation(); renderDays(); }
monthText.onclick=(e)=>{ e.stopPropagation(); renderMonths(); }
yearText.onclick=(e)=>{ e.stopPropagation(); renderYears(); }

// باز و بسته شدن تقویم با کلیک بیرون
document.addEventListener('click', function(e){
    if(!cal.contains(e.target) && e.target!==input){
        cal.style.display='none';
    }
});
input.addEventListener('click', function(e){ e.stopPropagation(); cal.style.display='block'; });

renderDays();
</script>
</body>
</html>
