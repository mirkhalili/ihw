# معماری سامانه

## 1. نمای کلی
سامانه یک برنامه وب PHP/MySQL است. وب‌سرور باید document root را روی `public/` قرار دهد. فایل‌های خارج از `public/` نباید مستقیماً از وب سرو شوند.

## 2. لایه‌ها
### public
صفحات و endpointهای وب:
- ورود/خروج
- داشبورد
- فهرست تجهیزات
- مشاهده و ویرایش تجهیز
- Wizard ثبت
- پرسنل
- کاربران
- رویدادها
- گزارش و خروجی
- دریافت فایل منبع

### app
`bootstrap.php` مسئول:
- بارگذاری config
- timezone
- session
- cookie policy
- headerهای امنیتی
- اتصال PDO
- قرار دادن IP و user جاری در متغیرهای session/connection برای history
- helperهای escape، redirect، CSRF، authentication، authorization، encryption، upload و audit

### app/Services
- `CsvImporter`: تشخیص delimiter، خواندن CSV، تبدیل pipe-listها، نرمال‌سازی چاپگر/اسکنر، استخراج Disk و RAM.
- `ActiveDirectory`: اتصال LDAP/LDAPS، تست اتصال و sync پرسنل.

### database
`schema.sql` نصب تازه را می‌سازد. migrationها برای ارتقای نصب موجود هستند.

### storage
فایل‌های CSV منبع در مسیر خصوصی ذخیره می‌شوند. `storage/.htaccess` نیز برای جلوگیری از دسترسی مستقیم در محیط‌های Apache نگهداری شده است.

## 3. مدل اصلی
چهار نوع تجهیز مستقل وجود دارد:
- computer
- printer
- scanner
- display

هر تجهیز یک رکورد در `assets` دارد. جزئیات متغیر در JSON فیلد `technical` نگهداری می‌شود. برای کامپیوتر، Disk و RAM ساختاریافته‌اند.

## 4. جریان داده
CSV → upload خصوصی → CsvImporter → رکورد مرحله‌ای session → تکمیل دستی → کنترل شماره اموال → تأیید نهایی → transaction → assets + usage_stats + disks + RAM + audit/history.

## 5. مرز امنیتی
ورودی کاربر قابل اعتماد نیست. تمام SQL باید prepared statement باشد، خروجی HTML با `e()` escape شود، عملیات POST حساس CSRF داشته باشد و فایل منبع از مسیر public جدا بماند.
