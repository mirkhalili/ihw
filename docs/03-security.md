# امنیت

## 1. نشست
- نام session از config گرفته می‌شود.
- cookie دارای HttpOnly است.
- SameSite روی Lax است.
- secure در صورت HTTPS فعال می‌شود.

## 2. CSRF
برای فرم‌های POST حساس از token تصادفی session استفاده می‌شود. تابع `verify_csrf()` باید قبل از عملیات state-changing اجرا شود.

## 3. SQL Injection
اتصال با PDO و `ATTR_EMULATE_PREPARES=false` تنظیم شده است. Queryهای دارای ورودی کاربر باید prepared statement باشند.

## 4. XSS
helper `e()` با `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` خروجی HTML را escape می‌کند. داده JSON/CSV هرگز نباید بدون escape مستقیم در HTML قرار گیرد.

## 5. کنترل دسترسی
Authentication و Authorization جدا هستند:
- `require_login()`: نیاز به ورود
- `require_role()`: نقش مجاز
- `can_manage_asset_type()`: مجوز نوع تجهیز و عملیات create/edit

## 6. رمز عبور
رمز کاربران با `password_hash(..., PASSWORD_DEFAULT)` ذخیره می‌شود و مقدار خام نباید در DB ذخیره شود.

## 7. رمز AD
Bind password در `ad_settings.bind_password_enc` به‌صورت encrypted ذخیره می‌شود. کلید از `IHW_APP_KEY` استفاده می‌کند؛ در نبود آن، از ترکیب رمز DB و ثابت برنامه مشتق می‌شود. برای محیط واقعی باید `IHW_APP_KEY` مستقل و محرمانه تنظیم شود.

## 8. فایل‌های CSV
فایل منبع خارج از document root منطقی برنامه قرار دارد. دسترسی باید فقط از endpoint احراز هویت‌شده `source_download.php` انجام شود.

## 9. Headerهای امنیتی
bootstrap این headerها را می‌گذارد:
- X-Frame-Options: SAMEORIGIN
- X-Content-Type-Options: nosniff
- Referrer-Policy: strict-origin-when-cross-origin
- Permissions-Policy: camera=(), microphone=(), geolocation=()

## 10. LDAP
برای LDAPS باید گواهی CA معتبر سرور استفاده شود. رمز Bind نباید در کد، README، issue یا chat ثبت شود.

## 11. Audit
تغییرات مهم باید قابل ردیابی باشند. audit شامل user، IP، user-agent، action، entity و details است.

## 12. الزامات استقرار
- document root فقط `public/`
- storage خصوصی و writable برای PHP
- secretها خارج از Git
- DB با کاربر محدود و نه root
- HTTPS در محیط واقعی
- backup منظم DB و فایل‌های منبع

## اصلاحات جدید
تابع e اکنون mixed را می‌پذیرد و مقادیر عددی را نیز امن escape می‌کند. حذف تجهیز فقط با POST و CSRF و permission سمت سرور انجام می‌شود و audit دارد.
