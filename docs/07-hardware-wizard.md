# Wizard ثبت مرحله‌ای

## مسیر
`public/asset_wizard.php`

## state
وضعیت Wizard در `$_SESSION['_hardware_wizard']` نگهداری می‌شود و شامل records هر مرحله است.

## مراحل
computer → printer → scanner → display → review

## هر مرحله
1. upload CSV
2. تحلیل خودکار
3. تکمیل دستی
4. ذخیره مرحله
5. ادامه یا skip

## فیلدهای دستی مشترک
- asset_no
- location
- personnel_id در مراحل تجهیزاتی که صفحه فعلی ارائه می‌کند

## کنترل duplicate
JavaScript به `asset_check.php?asset_no=...` درخواست می‌فرستد. اگر duplicate باشد:
- پیام هشدار
- دکمه ویرایش تجهیز موجود
- غیرفعال شدن next

این کنترل فقط UX است؛ اعتبارسنجی server-side همچنان الزامی است.

## Review
مرحله review خلاصه هر چهار بخش را نمایش می‌دهد و امکان بازگشت به مرحله هر نوع تجهیز را دارد.

## Finish
ثبت نهایی داخل transaction است. برای هر record معتبر:
- assets insert
- usage_stats برای کامپیوتر
- asset_disks
- asset_ram_slots
- audit

در خطا transaction rollback می‌شود.

## Skip
مرحله skip‌شده در session علامت‌گذاری می‌شود و در ثبت نهایی رکوردی برای آن نوع ساخته نمی‌شود.

## نکته مهم
Wizard فعلی بر مبنای یک CSV برای هر نوع مرحله طراحی شده است. انتقال خودکار MonitorDetails از CSV کامپیوتر به رکورد display باید در صورت نیاز طبق gap ثبت‌شده بررسی شود؛ صرف وجود MonitorDetails در computer CSV به‌معنی ثبت خودکار یک display نیست.

## Multi-unit
هر واحد شناسایی‌شده در مراحل printer/scanner/display ردیف مستقل دارد و شماره اموال، پرسنل و مکان جداگانه می‌گیرد. finish هر واحد را به assets مستقل تبدیل می‌کند.


## نسخه 0.1.0.8
تحلیل CSV یک‌بار انجام می‌شود و خروجی در session wizard نگهداری می‌شود. مراحل چاپگر، اسکنر و نمایشگر فقط زمانی نمایش داده می‌شوند که داده واقعی برای آن‌ها شناسایی شده باشد. انتخاب پرسنل با جستجوی متنی انجام می‌شود و اطلاعات شبکه به شکل آرایه/JSON نمایش داده می‌شود.
