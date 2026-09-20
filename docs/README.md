# مستندات رسمی سامانه سخت‌افزار دانشگاه آزاد اسلامی واحد یزد

این پوشه «مرجع انتقال دانش پروژه» است. هدف آن این است که یک برنامه‌نویس، مدیر فنی، کارشناس زیرساخت یا عامل هوش مصنوعی بتواند بدون اتکا به حافظه گفت‌وگوهای قبلی، ساختار، منطق، فرایندها، دیتابیس، امنیت، استقرار و روش ادامه توسعه سامانه را از روی مخزن بفهمد.

## اصل مرجع

ترتیب اعتبار برای تصمیم‌گیری فنی:

1. کد فعلی همان branch/commit
2. `database/schema.sql` و migrationها
3. این مستندات
4. README ریشه پروژه
5. توضیحات تاریخی PR/commit

اگر بین مستندات و کد اختلافی مشاهده شد، کد و schema فعلی مبنای اجرای واقعی هستند و مستندات باید به‌روزرسانی شوند.

## نقشه مستندات

- [01-architecture.md](01-architecture.md) — معماری، لایه‌ها و قراردادهای فنی
- [02-business-processes.md](02-business-processes.md) — شرح خدمات و تمام فرایندهای کسب‌وکار
- [03-database.md](03-database.md) — فرهنگ کامل دیتابیس، جداول، ستون‌ها، کلیدها و روابط
- [04-file-catalog.md](04-file-catalog.md) — شناسنامه فایل‌ها و مسئولیت هر فایل
- [05-csv-and-hardware-analysis.md](05-csv-and-hardware-analysis.md) — تحلیل CSV، نگاشت فیلدها، RAM، Disk و تجهیزات جانبی
- [06-auth-security.md](06-auth-security.md) — احراز هویت، نقش‌ها، دسترسی، CSRF، رمزنگاری و Audit
- [07-deployment-operations.md](07-deployment-operations.md) — نصب، Docker، AD، storage و عملیات
- [08-testing-and-acceptance.md](08-testing-and-acceptance.md) — تست‌های لازم، smoke test و معیار پذیرش
- [09-continuation-guide.md](09-continuation-guide.md) — راهنمای ادامه پروژه برای انسان یا AI
- [10-change-log.md](10-change-log.md) — قرارداد ثبت تغییرات و نسخه‌بندی

## وضعیت نسخه مستندات

نسخه مستندات هم‌نسخه پروژه است و باید همراه هر تغییر مهم به‌روزرسانی شود. نسخه فعلی این مجموعه: **1.0.0.3**.

## قرارداد نگهداری

هر توسعه‌دهنده پس از تغییر یکی از موارد زیر باید مستندات مربوط را نیز اصلاح کند:

- جدول یا ستون DB → `03-database.md`
- فایل PHP یا سرویس → `04-file-catalog.md`
- فرایند کاربر → `02-business-processes.md`
- parser/importer → `05-csv-and-hardware-analysis.md`
- احراز هویت/مجوز → `06-auth-security.md`
- Docker/AD/Deployment → `07-deployment-operations.md`
- تست یا معیار پذیرش → `08-testing-and-acceptance.md`

هر commit مرتبط با تغییر رفتاری باید نسخه `VERSION` را طبق قرارداد چهاررقمی پروژه بررسی کند.
