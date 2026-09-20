# 06 — احراز هویت و امنیت

## 1. Session

session با نام `ihw_session` استفاده می‌شود.

Cookieها:

- HttpOnly
- SameSite=Lax
- Secure در HTTPS

## 2. Password

رمز با `password_hash(..., PASSWORD_DEFAULT)` ذخیره می‌شود. رمز خام نباید در DB، log یا documentation ذخیره شود.

## 3. CSRF

فرم‌های POST از token session استفاده می‌کنند و قبل از پردازش `verify_csrf()` اجرا می‌شود.

## 4. Authorization

سه سطح مهم:

- login
- role
- asset-type permission

`can_manage_asset_type(type, action)` مجوز create/edit را برای چهار نوع بررسی می‌کند.

Admin برای عملیات مدیریتی است و permission table برای admin ملاک محدودکننده نیست.

## 5. AD secret

bind password در `ad_settings.bind_password_enc` ذخیره می‌شود.

رمزنگاری از Sodium secretbox استفاده می‌کند. کلید ترجیحی `IHW_APP_KEY` است.

اگر IHW_APP_KEY وجود نداشته باشد، کلید fallback از secret DB مشتق می‌شود؛ در deployment حرفه‌ای IHW_APP_KEY باید صریحاً تنظیم شود.

## 6. SQL

DB access با PDO و prepared statement انجام می‌شود. `ATTR_EMULATE_PREPARES=false` فعال است.

## 7. Output escaping

متن HTML باید با helper `e()` escape شود.

## 8. Upload

CSV باید extension مجاز داشته باشد و فایل در storage خصوصی ذخیره شود. مسیر upload نباید document root عمومی باشد.

## 9. Audit

برای عملیات مدیریتی و ثبت تجهیز audit باید ایجاد شود و شامل user/IP/time/action/entity باشد.

## 10. Headerهای امنیتی

bootstrap هدرهای:

- X-Frame-Options
- X-Content-Type-Options
- Referrer-Policy
- Permissions-Policy

را تنظیم می‌کند.

## 11. قواعد توسعه امن

هر توسعه‌دهنده باید:

- SQL را parameterized کند.
- ورودی را validate کند.
- خروجی HTML را escape کند.
- عملیات POST را CSRF-protect کند.
- permission را سمت server enforce کند.
- secret را commit نکند.
- upload را public نکند.
- تغییر مهم را audit کند.
