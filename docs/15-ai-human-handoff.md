# راهنمای ورود عامل انسانی یا هوش مصنوعی به پروژه

## هدف
عامل جدید باید بدون حدس بتواند پروژه را بفهمد.

## ترتیب کار
1. VERSION
2. این فایل‌ها در docs
3. README
4. schema.sql
5. migrationها به ترتیب
6. bootstrap
7. Services
8. asset_wizard
9. asset_view/edit
10. CI

## قبل از تغییر
- branch و commit فعلی را بخوان.
- فایل مرتبط را کامل بررسی کن.
- migrationهای قبلی را بررسی کن.
- رفتار فعلی را با docs مقایسه کن.
- اگر ambiguity وجود دارد آن را در known-gaps ثبت کن، نه اینکه حدس بزنی.

## قواعد غیرقابل شکستن
- config/config.php را برای حل مشکل deployment به‌صورت دستی تغییر نده؛ از env استفاده کن.
- secret در Git/مدرک/گفتگو ذخیره نکن.
- asset_no همیشه 7 رقم است.
- asset_type فقط چهار مقدار فعلی است.
- Disk و RAM کامپیوتر ساختاریافته‌اند.
- MonitorDetails متعلق به display است.
- اطلاعات شبکه کامپیوتر نباید دوباره‌کاری شود.
- usage_stats جدول مستقل است.
- audit و registration history را حذف یا دور نزن مگر با migration/دلیل مستند.

## روش توسعه
هر تغییر را کوچک و قابل تست نگه دار:
1. تغییر
2. syntax check
3. DB check در صورت نیاز
4. functional test
5. docs
6. version/commit

## روش پاسخ به bug
علت را از stack trace/schema/source پیدا کن. از اصلاح کورکورانه چند فایل خودداری کن. اگر مشکل migration است، وضعیت واقعی DB را با SHOW CREATE TABLE/SHOW COLUMNS تأیید کن.

## خروجی مورد انتظار از agent
برای هر تغییر:
- فایل‌های تغییرکرده
- دلیل
- تست انجام‌شده
- نتیجه
- migration مورد نیاز
- اثر روی امنیت
- اثر روی backward compatibility
را ثبت کند.
