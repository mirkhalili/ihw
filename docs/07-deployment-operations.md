# 07 — استقرار و عملیات

## 1. پیش‌نیاز

- PHP 8.2+
- MySQL 8+ یا MariaDB سازگار
- وب‌سرور با document root روی public
- PHP LDAP برای AD
- دسترسی نوشتن PHP به storage/uploads
- برای LDAPS گواهی CA معتبر

## 2. Docker

در ساختار فعلی پروژه، کل پروژه باید برای PHP و nginx قابل مشاهده باشد؛ زیرا `public/index.php` فایل `../app/bootstrap.php` را require می‌کند.

الگوی volume صحیح:

`./ihw:/var/www/html`

و nginx:

`root /var/www/html/public;`

Mount کردن فقط public باعث می‌شود app/config/database در مسیر مورد انتظار PHP وجود نداشته باشند.

## 3. Environment

متغیرهای اصلی:

- IHW_DB_DSN
- IHW_DB_USER
- IHW_DB_PASS
- IHW_APP_KEY
- IHW_BASE_URL
- IHW_UPLOAD_DIR در صورت نیاز

## 4. DB

برای نصب جدید:

`database/schema.sql`

برای نصب موجود: migrationها را دقیقاً به ترتیب نسخه اجرا کنید.

## 5. Admin

فرمان CLI:

`php bin/create_admin.php USERNAME PASSWORD "FULL NAME"`

در Docker نمونه:

`docker exec -it php-fpm php /var/www/html/bin/create_admin.php admin 'PASSWORD' 'مدیر سامانه'`

رمز واقعی را در chat، Git یا log قرار ندهید.

## 6. Upload permission

PHP-FPM باید owner/group یا permission لازم برای `storage/uploads/` داشته باشد.

در صورت Permission denied، ابتدا user اجرای PHP-FPM و permission/ownership پوشه بررسی شود.

## 7. Active Directory فعلی پروژه

برای دامنه نمونه این پروژه، مقادیر محیطی که قبلاً برای زیرساخت شناسایی شده‌اند:

- Domain: campus.yazd.iau.ir
- DC: DC1.campus.yazd.iau.ir
- IP: 192.168.210.29
- LDAP معمولاً port 389
- LDAPS معمولاً port 636 در صورت فعال بودن

Base DN معمول دامنه به شکل `DC=campus,DC=yazd,DC=iau,DC=ir` است، اما مقدار نهایی باید با ساختار واقعی AD تأیید شود.

Bind account باید یک حساب سرویس read-only باشد.

## 8. تست اتصال AD

از داخل container PHP بررسی شود:

- DNS resolution
- دسترسی TCP به 389/636
- وجود extension ldap
- bind
- search Base DN

## 9. Backup

حداقل:

- dump منظم DB
- backup storage/uploads
- backup environment secrets خارج از Git
- نگهداری نسخه migrationها

## 10. Rollback

قبل از migration:

1. DB backup
2. Git commit/branch مشخص
3. ثبت VERSION
4. اجرای migration
5. smoke test

در صورت failure، rollback migration فقط در صورتی انجام شود که migration rollback-safe باشد؛ در غیر این صورت restore backup انجام شود.

## 11. Production checklist

- HTTPS
- document root = public
- storage خارج از public
- IHW_APP_KEY تنظیم
- DB user محدود
- password قوی
- debug خاموش
- LDAP/LDAPS تست شده
- backup تست شده
- GitHub Actions سبز
