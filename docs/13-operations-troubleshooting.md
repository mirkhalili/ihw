# عملیات و عیب‌یابی

## خطای bootstrap
پیام شبیه:
`require(.../../app/bootstrap.php): No such file`

علت محتمل: container فقط public را mount کرده است. کل پروژه را mount و nginx root را public تنظیم کنید.

## Permission denied برای upload
علت: PHP user روی storage/uploads write ندارد.
راهکار: permission/owner و volume را اصلاح کنید.

## Foreign key usage_stats errno 150
علت رایج: تعریف child key با assets.asset_no یکسان نیست. نوع CHAR(7)، charset/collation و engine باید سازگار باشند. migration 006 نسخه repair این مورد را با ساختار موجود پوشش می‌دهد.

## Unknown column usage fields
علت: migration 003 روی نصب موجود اجرا نشده است.
راهکار: migration_003_usage_and_collection_wizard.sql را اجرا و SHOW COLUMNS را بررسی کنید.

## usage_stats خالی
ابتدا وجود ستون‌های usage در assets را بررسی کنید، سپس migration/insert انتقال usage را اجرا کنید.

## AD connection failure
به‌ترتیب:
1. `php -m | grep ldap`
2. DNS DC
3. TCP port
4. bind credential
5. Base DN/filter
6. certificate در LDAPS

## duplicate asset
از asset_check استفاده و سپس asset_edit برای تجهیز موجود.

## خطای 403
role و user_asset_permissions را بررسی کنید. admin از type permission مستثنا است.

## خطای 419
CSRF token نامعتبر یا session از بین رفته است.

## روال گزارش خطا
در گزارش مشکل همیشه این موارد را ثبت کنید:
- نسخه VERSION
- commit SHA
- endpoint
- زمان
- خطای کامل PHP/SQL
- migrationهای اجراشده
- نتیجه SHOW CREATE TABLE مربوط
- بدون ثبت secret/password
