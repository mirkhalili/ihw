# 09 — راهنمای ادامه پروژه برای انسان یا هوش مصنوعی

## 1. قبل از هر تغییر

عامل ادامه‌دهنده باید این فایل‌ها را بخواند:

1. `README.md`
2. `VERSION`
3. `docs/README.md`
4. `docs/01-architecture.md`
5. `docs/02-business-processes.md`
6. `docs/03-database.md`
7. فایل هدف
8. schema و migration مرتبط

## 2. اولویت بررسی

اگر task مربوط به ثبت تجهیز است:

`asset_wizard.php → CsvImporter.php → schema.sql → asset_edit.php → asset_view.php → audit`

اگر task مربوط AD است:

`directory.php → ActiveDirectory.php → personnel.php → ad_settings/personnel schema`

اگر task مربوط permission است:

`bootstrap.php → users.php → user_asset_permissions`

## 3. قبل از کدنویسی

مشخص کنید:

- ورودی چیست؟
- actor چه کسی است؟
- authorization کجاست؟
- entity اصلی چیست؟
- DB transaction لازم است؟
- audit لازم است؟
- migration لازم است؟
- backward compatibility لازم است؟
- تست قابل اجرای آن چیست؟

## 4. قوانین تغییر DB

هر تغییر schema:

1. migration بسازد.
2. schema نصب جدید را هم در صورت نیاز به‌روز کند.
3. docs/03-database.md را اصلاح کند.
4. migration را روی DB آزمایشی اجرا کند.
5. rollback/backup strategy را مشخص کند.

## 5. قوانین تغییر UI

اگر field اضافه شد:

- source CSV
- importer normalization
- wizard form
- save logic
- edit logic
- view logic
- report
- docs

بررسی شود.

## 6. قوانین تغییر CSV

اگر field جدید اضافه شد:

- مشخص شود scalar است یا array.
- delimiter و encoding بررسی شود.
- parser test اضافه شود.
- mapping مقصد مشخص شود.

## 7. قوانین تغییر permission

هر permission جدید باید:

- role semantics
- server-side enforcement
- UI visibility
- audit
- test matrix
- documentation

داشته باشد.

## 8. خروجی هر مرحله توسعه

عامل توسعه‌دهنده باید در پایان گزارش کند:

- VERSION جدید
- branch/commit
- فایل‌های تغییرکرده
- DB migration
- تست‌های اجراشده
- نتیجه تست‌ها
- موارد باقی‌مانده
- لینک branch/PR/release

## 9. مواردی که نباید حدس زده شوند

عامل نباید بدون بررسی کد/DB فرض کند:

- نام ستون جدید
- نقش جدید
- ساختار OU در AD
- password یا secret
- نام فیلد CSV
- رفتار migration
- مسیر Docker

در نبود شواهد، باید موضوع را به‌عنوان «نیازمند تأیید» ثبت کند.

## 10. قرارداد مستندسازی

مستندات باید توضیح دهند «سیستم اکنون چگونه کار می‌کند»، نه اینکه صرفاً طراحی ایده‌آل آینده را توصیف کنند. اگر feature هنوز کامل نشده، با عنوان «وضعیت فعلی/محدودیت» ثبت شود.
