# استقرار

## 1. پیش‌نیاز
- PHP 8.2+
- MySQL 8+ یا MariaDB سازگار
- PHP LDAP برای AD
- وب‌سرور با document root روی public

## 2. ساختار Docker مورد آزمایش
اگر کل پروژه در `/var/www/html` mount می‌شود:
- nginx root باید `/var/www/html/public` باشد.
- PHP نیز کل پروژه را در `/var/www/html` ببیند.

اشتباه رایج: mount کردن فقط `public/` در PHP و سپس شکستن مسیر `../app/bootstrap.php`.

## 3. Environment
- IHW_DB_DSN
- IHW_DB_USER
- IHW_DB_PASS
- IHW_BASE_URL
- IHW_APP_KEY
- در صورت نیاز IHW_UPLOAD_DIR

فایل `config/config.php` قرارداد ثابت برنامه است و نباید برای هر deployment دستی تغییر کند؛ deployment باید env مناسب بدهد.

## 4. Database
نصب تازه:
`database/schema.sql`

نصب موجود:
migrationها را طبق مستندات نسخه اجرا کنید. migration repair را فقط وقتی نیاز است اجرا کنید؛ اسکریپتهای repair فعلی idempotent طراحی شده‌اند.

## 5. Admin
دستور:
`php bin/create_admin.php USERNAME PASSWORD "نام"`

رمز واقعی را در shell history/issue/chat عمومی ثبت نکنید.

## 6. Upload
PHP باید به `storage/uploads` دسترسی نوشتن داشته باشد. در صورت خطای Permission denied:
- owner/group کانتینر PHP
- mode directory
- mount volume
- SELinux/AppArmor در صورت وجود
را بررسی کنید.

## 7. AD
داخل کانتینر PHP باید LDAP نصب باشد و DNS/network به DC دسترسی داشته باشد.

## 8. HTTPS
در محیط واقعی HTTPS اجباری عملیاتی در نظر گرفته شود تا cookie secure و credentialهای AD/DB امن باشند.

## 9. Backup
حداقل:
- DB dump
- storage/uploads
- environment secrets
باید backup و restore test داشته باشند.
