# نقشه فایل‌ها و صفحات

## ریشه
- README.md: معرفی و نصب سریع
- VERSION: نسخه چهارقسمتی
- .env.example: نمونه environment
- .github/workflows/php-lint.yml: CI syntax check

## app
- bootstrap.php: هسته مشترک request
- Services/ActiveDirectory.php: LDAP/AD
- Services/CsvImporter.php: import

## bin
- create_admin.php: ساخت/به‌روزرسانی admin از CLI
- smoke_csv.php: smoke test CSV

## config
- config.php: configuration اصلی؛ در deployment فعلی envها را می‌خواند.

## database
- schema.sql: نصب تازه
- migration_001_hardening.sql
- migration_002_ad_and_permissions.sql
- migration_003_hardware_fields.sql
- migration_003_usage_and_collection_wizard.sql
- migration_004_usage_stats.sql
- migration_005_registration_history.sql
- migration_006_usage_stats_repair.sql

## public
- index.php: dashboard
- login.php / logout.php: auth
- assets.php: list/search/pagination
- asset_new.php: فرم ثبت قدیمی/مستقل
- asset_edit.php: ویرایش
- asset_view.php: پرونده
- asset_check.php: duplicate check
- asset_wizard.php: workflow اصلی ثبت مرحله‌ای
- users.php: مدیریت کاربران
- personnel.php: پرسنل/AD
- audit.php: رویدادها
- report.php: گزارش
- export.php: CSV export
- source_download.php: دانلود کنترل‌شده منبع
- assets/app.css: UI

## storage
- uploads/: فایل‌های CSV خصوصی
- .htaccess: محدودسازی دسترسی مستقیم در Apache

## قرارداد تغییر
هر endpoint جدید باید:
1. bootstrap را load کند
2. auth مناسب داشته باشد
3. CSRF برای POST state-changing داشته باشد
4. input validation داشته باشد
5. audit در عملیات مهم داشته باشد
6. docs مربوطه را به‌روز کند.

## فایل جدید
asset_delete.php endpoint حذف امن تجهیز است. asset_view.php اکنون کارت‌های اطلاعاتی JSON/Disk/RAM و دکمه حذف دارد.
