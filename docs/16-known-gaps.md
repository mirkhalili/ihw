# موارد نیازمند بررسی آینده

این فایل عمداً مواردی را جدا می‌کند که نباید صرفاً بر اساس هدف پروژه به‌عنوان قابلیت قطعی فرض شوند.

## 1. انتقال MonitorDetails
Wizard فیلد MonitorDetails را در مرحله display در صورت وجود در record نمایش می‌دهد، اما از کد فعلی نمی‌توان ثبت خودکار یک display مستقل از CSV کامپیوتر را قطعی اعلام کرد. اگر این رفتار لازم است باید mapping و policy دقیق طراحی و تست شود.

## 2. asset_new در کنار Wizard
`asset_new.php` هنوز در repository وجود دارد. قبل از حذف یا deprecate کردن باید بررسی شود آیا deployment یا workflow دیگری به آن وابسته است.

## 3. فیلد department در assets
schema دارای department است، در حالی که UI جدید عمدتاً location را به‌عنوان فیلد دستی مطرح می‌کند. حذف ستون از DB بدون migration و بررسی گزارش‌ها مجاز نیست.

## 4. legacy usage columns
assets هنوز ستون‌های usage از migration 003 را دارد و usage_stats نیز وجود دارد. در آینده باید تصمیم گرفته شود آیا legacy columns فقط compatibility هستند یا باید حذف/فریز شوند.

## 5. تاریخ‌ها
فرمت واقعی LastBootTime/LastShutdownTime/CollectedAt باید در نمونه‌های مختلف CSV بررسی شود. parser فعلی migration 006 فرمت خاصی را پوشش می‌دهد.

## 6. ثبت IP تاریخچه
registration_history از متغیرهای connection-level PDO استفاده می‌کند. هر مسیر درج asset خارج از bootstrap یا connection مورد انتظار باید بررسی شود.

## 7. تست end-to-end
CI فعلی syntax PHP را پوشش می‌دهد، نه یک browser/e2e کامل. برای production بهتر است تست integration/e2e و migration-on-empty-db اضافه شود.

## 8. schema drift
وجود چند migration usage نشان می‌دهد schema در طول توسعه تغییر کرده است. در آینده migration ledger/version table می‌تواند از اجرای اشتباه migration جلوگیری کند.

## 9. امنیت عملیاتی
CSP، rate limiting ورود، password policy قوی‌تر، rotation برای app key و backup encryption هنوز باید بر اساس نیاز محیط عملیاتی بررسی شوند.

## 10. AD lifecycle
sync فعلی insert/update را پوشش می‌دهد؛ سیاست حذف یا غیرفعال‌سازی پرسنلی که از AD حذف شده‌اند باید به‌صورت business rule مشخص شود.

## قانون این فایل
هیچ مورد اینجا را «bug قطعی» ندانید مگر اینکه با تست یا نیاز کسب‌وکار تأیید شده باشد.

## Legacy multi-asset
داده‌های قدیمی که Printer/Scanner/Display را داخل computer ذخیره کرده‌اند خودکار تفکیک نمی‌شوند، زیرا asset_no و personnel مستقل لازم است. اصلاح باید با منبع CSV و تأیید کاربر انجام شود. parsing متن آزاد manufacturer/model نیز نیازمند نمونه‌های بیشتر HWiNFO است.
