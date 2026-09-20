# نسخه‌بندی و راهنمای تغییر

## قالب
نسخه چهارقسمتی:
`MAJOR.MINOR.PATCH.BUILD`

در پروژه فعلی شماره BUILD برای تحویل‌های مرحله‌ای افزایش می‌یابد.

## قانون
هر تغییر قابل تحویل:
1. VERSION را به‌روزرسانی کند.
2. docs مرتبط را به‌روز کند.
3. تست انجام شود.
4. commit message روشن داشته باشد.
5. branch مناسب مشخص باشد.

## تغییر دیتابیس
اگر schema تغییر کرد:
- schema.sql برای نصب تازه
- migration برای نصب موجود
- docs/database.md
- docs/data-model.md
باید هماهنگ شوند.

## تغییر فیلد CSV
- CsvImporter
- Wizard labels/stage fields
- view/edit
- docs/csv-import.md
- در صورت نیاز DB

## تغییر permission
- role logic
- UI
- endpoint authorization
- docs/roles-permissions.md
- test

## تغییر امنیت
هر تغییر auth/session/CSRF/upload/encryption باید regression test امنیتی داشته باشد.

## Git
تغییرات feature را روی branch مناسب انجام دهید. قبل از merge به main، CI و تست کاربردی باید سبز باشد.

## release
تا زمانی که tag/release واقعی ساخته نشده، archive شاخه develop را release رسمی فرض نکنید.
