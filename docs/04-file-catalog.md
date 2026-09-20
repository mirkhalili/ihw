# 04 — شناسنامه فایل‌ها

## public/

### index.php
داشبورد اصلی، شمارش چهار نوع تجهیز، آخرین رکوردها و دسترسی‌های مدیریتی.

### asset_wizard.php
فرایند ثبت مرحله‌ای چهار نوع تجهیز، تحلیل CSV، تکمیل دستی، کنترل شماره اموال، مرور و ثبت نهایی transaction.

### asset_new.php
ثبت مستقیم تجهیز در جریان‌هایی که از wizard استفاده نمی‌کنند یا برای عملیات مستقل.

### asset_edit.php
ویرایش تجهیز و داده‌های وابسته با رعایت permission.

### asset_view.php
نمایش جزئیات کامل تجهیز، Disk/RAM/usage و سابقه ثبت/تغییر.

### asset_check.php
بررسی سریع وجود شماره اموال برای کنترل AJAX.

### assets.php
فهرست تجهیزات، search/filter/pagination.

### report.php
گزارش‌گیری و خروجی‌های گزارش.

### users.php
مدیریت کاربران، نقش‌ها و permission چهار پایگاه.

### directory.php
تنظیم، تست و sync Active Directory.

### personnel.php
نمایش و جستجوی personnelهای sync شده از AD.

### login.php / logout.php
ورود و خروج session.

### audit.php
نمایش رویدادهای audit برای مدیر.

## app/

### bootstrap.php
هسته runtime:
- config
- PDO
- session
- security headers
- CSRF
- auth
- role
- permission
- encryption
- upload storage
- audit

### Services/ActiveDirectory.php
LDAP/LDAPS bind، query و sync پرسنل.

### Services/CsvImporter.php
خواندن CSV، delimiter detection، حذف BOM، pipe normalization، disk parser و RAM parser.

## database/

### schema.sql
ساخت کامل DB نصب جدید.

### migration_*.sql
تغییرات مرحله‌ای schema برای نصب‌های موجود.

## config/

### config.php
تنظیمات application و DB. secretهای deployment بهتر است از environment بیایند.

## bin/

### create_admin.php
ساخت/به‌روزرسانی حساب admin از CLI.

## storage/

`storage/uploads/` برای فایل‌های CSV منبع خصوصی است.

## assets/

CSS و منابع رابط کاربری.

## .github/workflows/

GitHub Actions برای lint و smoke testهای PHP/CSV.

## قرارداد نام‌گذاری

فایل‌های public مسئول request/response هستند؛ سرویس‌های قابل استفاده مجدد باید در app/Services باشند. منطق تکراری را بین صفحات کپی نکنید.
