# 08 — تست و معیار پذیرش

## 1. PHP syntax

برای تمام PHPهای public، app و bin:

`php -l path/to/file.php`

هیچ parse error قابل قبول نیست.

## 2. CSV smoke test

حداقل یک CSV واقعی باید:

- خوانده شود.
- header تشخیص داده شود.
- delimiter تشخیص داده شود.
- pipe values array شوند.
- Disk parser خروجی بدهد.
- RAM parser خروجی بدهد.

## 3. Asset number

تست‌ها:

- ۷ رقم → قبول
- کمتر از ۷ → رد
- بیشتر از ۷ → رد
- حروف → رد
- duplicate موجود → رد و لینک edit
- duplicate بین چهار مرحله wizard → رد

## 4. Wizard

سناریوی کامل:

1. login با collector/expert مجاز
2. upload CSV
3. تکمیل کامپیوتر
4. تکمیل printer
5. تکمیل scanner
6. تکمیل display
7. review
8. final approval
9. بررسی وجود چهار asset
10. بررسی audit
11. بررسی registration_at/ip/by
12. بررسی CollectedAt

## 5. Transaction

باید سناریویی ایجاد شود که insert یکی از چهار مرحله خطا دهد. انتظار:

- هیچ asset ناقصی باقی نماند.
- usage/disks/RAM مرتبط نیز commit نشده باشند.
- audit نهایی ناقص باقی نماند.

## 6. Permission

برای هر role:

| عملیات | admin | collector | expert | viewer |
|---|---:|---:|---:|---:|
| مشاهده | بله | بله | بله | بله |
| create | بله | طبق permission | طبق permission | خیر |
| edit | بله | طبق permission | طبق permission | خیر |
| users | بله | خیر | خیر | خیر |
| AD settings | بله | خیر | خیر | خیر |

همچنین هر asset_type باید مستقل تست شود.

## 7. Security

- POST بدون CSRF → رد
- user بدون login → redirect/login
- role نامجاز → 403
- SQL injection در search → نباید query را بشکند
- XSS در نام/model/location → باید escape شود
- bind password → نباید در UI یا audit plaintext نمایش داده شود

## 8. AD

- extension ldap
- DNS
- port
- bind
- search
- sync
- تکرار sync نباید personnel duplicate ایجاد کند.

## 9. Regression

بعد از هر تغییر مهم:

1. lint
2. CSV smoke
3. schema/migration check
4. wizard test
5. permission test
6. audit test

## 10. معیار پذیرش release

Release زمانی قابل تحویل عملیاتی است که:

- syntax همه فایل‌ها موفق باشد.
- smoke test CSV موفق باشد.
- schema/migration با DB تست شده باشد.
- wizard end-to-end موفق باشد.
- permissionها تست شده باشند.
- audit قابل مشاهده باشد.
- upload قابل نوشتن باشد.
- GitHub Actions موفق باشد.
- VERSION با تغییرات همخوان باشد.
