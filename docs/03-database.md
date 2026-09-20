# 03 — فرهنگ دیتابیس

DB فعلی `ihw` با charset/collation برابر utf8mb4 تعریف می‌شود.

## 1. ER کلی

`roles` 1→N `users`

`users` 1→N `audit_logs`

`users` 1→N `assets` از مسیر created_by و updated_by

`personnel` 1→N `assets`

`assets` 1→N `asset_disks`

`assets` 1→N `asset_ram_slots`

`assets` 1→1 `usage_stats`

`users` N↔N نوع تجهیز از طریق `user_asset_permissions`

`ad_settings` یک رکورد تنظیمات فعال دارد.

`import_batches` سابقه batch import را نگهداری می‌کند.

## 2. roles

| ستون | نوع | کاربرد |
|---|---|---|
| id | BIGINT UNSIGNED PK | شناسه نقش |
| name | VARCHAR(50) UNIQUE | کلید فنی نقش |
| title | VARCHAR(100) | عنوان فارسی |
| created_at | TIMESTAMP | زمان ایجاد |

نقش‌های پایه: admin, collector, viewer, expert.

## 3. users

| ستون | نوع | کاربرد |
|---|---|---|
| id | BIGINT UNSIGNED PK | شناسه |
| username | VARCHAR(100) UNIQUE | نام ورود |
| password_hash | VARCHAR(255) | hash رمز |
| full_name | VARCHAR(160) | نام نمایشی |
| role_id | FK roles | نقش |
| is_active | TINYINT | فعال/غیرفعال |
| created_at | TIMESTAMP | ایجاد |
| updated_at | TIMESTAMP | آخرین تغییر |

رمز خام هرگز ذخیره نمی‌شود.

## 4. personnel

| ستون | نوع | کاربرد |
|---|---|---|
| id | BIGINT UNSIGNED PK | شناسه داخلی |
| personnel_code | VARCHAR(100) UNIQUE | employeeID یا sAMAccountName |
| national_id | VARCHAR(20) | فیلد موجود برای شناسه ملی |
| full_name | VARCHAR(200) | نام |
| department | VARCHAR(255) | اداره/واحد |
| position | VARCHAR(255) | سمت |
| extra | JSON | اطلاعات تکمیلی AD |
| created_at | TIMESTAMP | ایجاد |
| updated_at | TIMESTAMP | تغییر |

منبع اصلی فهرست فعلی Active Directory است.

## 5. assets

جدول مرکزی همه تجهیزات.

| ستون | نوع | کاربرد |
|---|---|---|
| asset_no | CHAR(7) PK | شماره اموال ۷ رقمی |
| asset_type | ENUM | computer/printer/display/scanner |
| device_subtype | VARCHAR(80) | زیرنوع |
| hostname | VARCHAR(255) | نام دستگاه |
| personnel_id | FK | مسئول تجهیز |
| location | VARCHAR(500) | مکان |
| department | VARCHAR(255) | واحد |
| manufacturer | VARCHAR(255) | سازنده |
| model | VARCHAR(255) | مدل |
| serial_no | VARCHAR(255) | سریال |
| technical | JSON | مشخصات متغیر |
| status | ENUM | active/repair/retired/lost |
| source_file | VARCHAR(255) | نام CSV منبع |
| created_by | FK users | ثبت‌کننده |
| updated_by | FK users | آخرین ویرایش‌کننده |
| created_at | TIMESTAMP | زمان ایجاد |
| updated_at | TIMESTAMP | زمان تغییر |
| registration_ip | VARCHAR(45) | IP ثبت نهایی |
| registration_at | DATETIME | زمان ثبت نهایی |

`asset_type` تعیین می‌کند رکورد متعلق به کدام پایگاه منطقی است.

## 6. asset_disks

اجزای فیزیکی دیسک کامپیوتر.

- id
- asset_no FK
- disk_index
- model
- size_gb
- serial_no
- interface_name
- media
- created_at
- updated_at

کلید یکتا: `asset_no + disk_index`.

## 7. asset_ram_slots

جزئیات اسلات‌های RAM.

- id
- asset_no FK
- slot_no
- state: empty/occupied/unknown
- capacity_gb
- ram_type
- speed_mhz
- manufacturer
- part_number
- serial_no
- created_at
- updated_at

کلید یکتا: `asset_no + slot_no`.

## 8. usage_stats

برای هر کامپیوتر یک رکورد آماری:

- boot_count
- normal_shutdown_count
- unexpected_shutdown_count
- user_shutdown_count
- last_boot_time
- last_shutdown_time
- completed_session_count
- current_session_hours
- current_session_duration
- total_usage_hours
- total_usage_duration
- average_session_hours
- average_session_duration
- longest_session_hours
- longest_session_duration
- collected_at

`collected_at` تاریخ برداشت اطلاعات است، نه الزاماً زمان ثبت در سامانه.

## 9. audit_logs

تمام رویدادهای عملیاتی مهم:

- user_id
- action
- entity_type
- entity_id
- ip_address
- user_agent
- details JSON
- created_at

برای traceability نباید این جدول حذف یا دور زده شود.

## 10. import_batches

برای batchهای import:

- import_type: personnel/hardware
- filename
- total_rows
- imported_rows
- failed_rows
- errors JSON
- user_id
- created_at

## 11. ad_settings

تنظیمات AD:

- host
- port
- use_tls
- base_dn
- bind_dn
- bind_password_enc
- user_filter
- updated_by
- updated_at

رمز bind به‌صورت encrypted ذخیره می‌شود.

## 12. user_asset_permissions

مجوز هر کاربر برای هر asset_type:

- user_id
- asset_type
- can_create
- can_edit

کلید اصلی مرکب: `user_id + asset_type`.

## 13. migration ثبت سابقه

فایل `database/migration_005_registration_history.sql` دو ستون `registration_ip` و `registration_at` را به `assets` اضافه و روی `registration_at` index ایجاد می‌کند. این migration برای نصب‌های موجودی که schema پایه آنها این دو ستون را ندارد الزامی است.

## 14. نکات migration

در نصب جدید schema کامل اجرا می‌شود.

در نصب موجود migrationها باید طبق README و ترتیب نسخه فعلی اجرا شوند. هیچ migration قدیمی نباید بدون بررسی وضعیت DB دوباره اجرا شود.

## 15. قواعد integrity

- asset_no باید دقیقاً ۷ رقم باشد.
- asset_no در کل سامانه unique است.
- personnel حذف فیزیکی نمی‌شود مگر با تصمیم صریح migration؛ FK روی asset در حذف SET NULL است.
- حذف asset باید داده‌های disks/RAM/usage وابسته را طبق ON DELETE CASCADE مدیریت کند.
