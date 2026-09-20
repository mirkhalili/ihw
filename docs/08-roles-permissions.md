# نقش‌ها و دسترسی‌ها

## نقش‌ها
### admin
مدیریت کاربران، تجهیزات، گزارش‌ها، audit، AD و دسترسی‌های نوع تجهیز. در `can_manage_asset_type` مدیر مستقل از permission row مجاز است.

### collector
برداشت و ثبت/ویرایش فقط در صورت داشتن مجوز نوع تجهیز.

### expert
مشابه collector از نظر قابلیت ویرایش تجهیزات، مشروط به permission.

### viewer
مشاهده و گزارش‌گیری بدون ویرایش.

## جدول user_asset_permissions
برای collector/expert:
- can_create
- can_edit

کلید ترکیبی user_id + asset_type است.

## سیاست UI
مخفی کردن دکمه به‌تنهایی امنیت نیست. endpoint نیز باید permission را بررسی کند.

## اضافه کردن نوع جدید
اگر asset_type جدید اضافه شود باید:
- ENUMها
- permission table
- Wizard
- view/edit
- labels
- گزارش
- مستندات
همزمان تغییر کنند.
