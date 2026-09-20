# تست

## 1. Static
CI فعلی syntax PHP را بررسی می‌کند. هر تغییر PHP باید بدون syntax error باشد.

## 2. Smoke CSV
`bin/smoke_csv.php` برای smoke test parser وجود دارد.

## 3. تست دیتابیس
پس از migrationهای usage:
```sql
SELECT COUNT(*) FROM assets WHERE asset_type='computer';
SELECT COUNT(*) FROM usage_stats;
SHOW CREATE TABLE usage_stats;
```

## 4. تست Wizard
سناریو:
1. ورود collector/expert مجاز
2. upload CSV computer
3. بررسی auto-fill
4. اصلاح manual fields
5. ثبت شماره 7 رقمی
6. duplicate check
7. printer
8. scanner
9. display
10. review
11. finish
12. بررسی transaction و audit

## 5. تست duplicate
شماره موجود را وارد کنید. انتظار:
- هشدار
- edit link
- next disabled
- server-side duplicate protection

## 6. تست امنیت
- دسترسی anonymous به صفحات محافظت‌شده
- role escalation
- CSRF
- XSS در fields
- SQL injection در search/filter
- دسترسی مستقیم به source file
- invalid asset_no
- invalid asset_type

## 7. تست AD
- DNS
- TCP 389/636
- LDAP bind
- search
- sync
- duplicate personnel_code
- empty employeeID fallback

## 8. معیار پذیرش
هیچ خطای PHP/SQL نباید در سناریوهای اصلی باقی بماند؛ داده imported باید در view قابل ردیابی باشد و audit برای عملیات مهم وجود داشته باشد.

## 9. تست فعلی انجام‌شده
در محیط آزمایشی اخیر، انتقال usage با موفقیت انجام شد و دو رکورد usage_stats ایجاد شد.
