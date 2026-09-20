# پایگاه داده

## 1. جداول
### roles
نقش‌های سامانه:
- admin
- collector
- viewer
- expert

کلید: `id`. نام نقش unique است.

### users
کاربران برنامه.
فیلدهای مهم:
- id
- username unique
- password_hash
- full_name
- role_id
- is_active
- created_at / updated_at

### personnel
فهرست پرسنل همگام‌شده:
- id
- personnel_code unique nullable
- national_id
- full_name
- department
- position
- extra JSON

### assets
هسته تجهیزات:
- asset_no CHAR(7): PK
- asset_type: computer/printer/display/scanner
- device_subtype
- hostname
- personnel_id
- location
- department
- manufacturer
- model
- serial_no
- technical JSON
- status: active/repair/retired/lost
- source_file
- created_by / updated_by
- created_at / updated_at

شماره اموال با CHECK به الگوی 7 رقم محدود شده است.

### asset_disks
دیسک‌های فیزیکی کامپیوتر:
- id
- asset_no FK
- disk_index
- model
- size_gb
- serial_no
- interface_name
- media

ترکیب asset_no + disk_index unique است.

### asset_ram_slots
ماژول‌های RAM:
- id
- asset_no FK
- slot_no
- state
- capacity_gb
- ram_type
- speed_mhz
- manufacturer
- part_number
- serial_no

ترکیب asset_no + slot_no unique است.

### usage_stats
آمار استفاده کامپیوتر:
asset_no PK/FK و 16 شاخص:
boot_count، normal_shutdown_count، unexpected_shutdown_count، user_shutdown_count، last_boot_time، last_shutdown_time، completed_session_count، current_session_hours، current_session_duration، total_usage_hours، total_usage_duration، average_session_hours، average_session_duration، longest_session_hours، longest_session_duration، collected_at.

### audit_logs
ردیابی عملیات:
user_id، action، entity_type، entity_id، ip_address، user_agent، details JSON، created_at.

### import_batches
ردیابی import:
import_type، filename، total_rows، imported_rows، failed_rows، errors JSON، user_id، created_at.

### ad_settings
تنظیم LDAP/LDAPS:
host، port، use_tls، base_dn، bind_dn، bind_password_enc، user_filter، updated_by، updated_at.

### user_asset_permissions
مجوز هر کاربر برای هر نوع تجهیز:
user_id + asset_type PK، can_create، can_edit.

### registration_history
تاریخچه ثبت:
asset_no، collected_at، registered_at، registered_ip، registered_by.

## 2. روابط اصلی
users → roles
assets → personnel
assets → users(created_by/updated_by)
asset_disks → assets
asset_ram_slots → assets
usage_stats → assets
audit_logs → users
import_batches → users
ad_settings → users(updated_by)
user_asset_permissions → users
registration_history → assets/users

## 3. سیاست حذف
جداول وابسته تجهیزات در موارد تعریف‌شده ON DELETE CASCADE دارند. personnel و users در روابط مناسب SET NULL می‌شوند.

## 4. migration
migrationها باید به ترتیب منطقی نصب شوند. `006_usage_stats_repair.sql` برای تعمیر نصب‌هایی است که usage_stats ناقص ساخته شده است و با ساختار CHAR(7)/collation فعلی سازگار شده است.

## 5. وضعیت واقعی نصب آزمایشی
در تست اخیر:
- تعداد کامپیوترها: 2
- تعداد رکوردهای usage_stats: 2
این نتیجه مربوط به محیط آزمایشی کاربر است و بخشی از schema repository محسوب نمی‌شود.

## تفکیک تجهیزات
Printer/Scanner/Display هرکدام باید رکورد مستقل assets داشته باشند و personnel_id مستقل داشته باشند. usage_stats زمان‌ها را DATETIME نگه می‌دارد. Disk/RAM/usage_stats وابسته به asset هستند و cascade delete دارند.
