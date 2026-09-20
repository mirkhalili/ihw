# Active Directory

## سرویس
`app/Services/ActiveDirectory.php`

## تنظیمات
در `ad_settings`:
- host
- port
- use_tls
- base_dn
- bind_dn
- bind_password_enc
- user_filter

## اتصال
سرویس از LDAP v3 استفاده می‌کند و referrals را خاموش می‌کند. scheme بر اساس use_tls بین ldap و ldaps انتخاب می‌شود.

## Sync
Attributes:
sAMAccountName، displayName، givenName، sn، employeeID، department، title، mail، userPrincipalName، distinguishedName

کد پرسنلی:
1. employeeID
2. در نبود آن sAMAccountName

نام:
1. displayName
2. در نبود آن givenName + sn

department و title نیز ذخیره می‌شوند و اطلاعات تکمیلی در extra JSON نگهداری می‌شود.

## عملیات
مدیر:
1. تنظیم host/port/base DN/filter
2. ثبت bind account
3. Test
4. Sync

## محیط نمونه سازمان
مقادیر کشف‌شده در محیط کاری پروژه در مستندات deployment قابل ذکرند؛ رمز Bind هرگز در repository ثبت نمی‌شود.

## خطاهای رایج
- افزونه PHP LDAP نصب نیست
- DNS نام DC را resolve نمی‌کند
- پورت 389/636 بسته است
- bind account اشتباه
- Base DN اشتباه
- filter بیش از حد محدود
- certificate برای LDAPS معتبر نیست

## اصل امنیتی
Bind account باید حداقل دسترسی لازم را داشته باشد.
