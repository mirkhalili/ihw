# 05 — تحلیل CSV و تجهیزات

## 1. منبع

CSV خروجی ابزار جمع‌آوری سخت‌افزار/HWiNFO می‌تواند شامل اطلاعات سیستم، CPU، RAM، motherboard، BIOS، disk، network، GPU، sound، monitor، printer، scanner و usage باشد.

## 2. CsvImporter

کلاس `CsvImporter` این مراحل را انجام می‌دهد:

1. فایل را binary باز می‌کند.
2. BOM UTF-8 را حذف می‌کند.
3. delimiter را بین comma/semicolon/tab تشخیص می‌دهد.
4. header را می‌خواند.
5. هر row را با header ترکیب می‌کند.
6. فیلدهای آرایه‌ای را normalize می‌کند.

## 3. pipe-separated values

فیلدهای زیر array می‌شوند:

- IPAddresses
- MACAddresses
- SubnetMasks
- Gateways
- DNSServers
- RAMManufacturers
- RAMPartNumbers
- RAMSerialNumbers
- RAMSpeedsMHz
- DiskModels
- DiskSizesGB
- DiskSerials
- DiskInterfaces

مثال:

`10.0.0.1|10.0.0.2`

به آرایه JSON تبدیل می‌شود.

## 4. Disk

اولویت parser:

1. DiskModels/DiskSizesGB/DiskSerials/DiskInterfaces اگر موجود باشند.
2. در غیر این صورت DiskDetails.

DiskDetails رکوردها را بر اساس الگوی مدل / size GB جدا می‌کند و serial/interface/media را استخراج می‌کند.

مقصد نهایی: `asset_disks`.

## 5. RAM

`RAMSlotDetails` رکوردهای slot را parse می‌کند و ظرفیت، سرعت، نوع، سازنده، part number و serial را استخراج می‌کند.

مقصد نهایی: `asset_ram_slots`.

## 6. Network

`NetworkDetails` به‌عنوان منبع اصلی جزئیات کامل شبکه نگهداری می‌شود.

در UI کامپیوتر اطلاعات شبکه تکراری IP/MAC/Subnet/Gateway/DNS نباید به‌صورت چند بخش تکراری نمایش داده شود.

## 7. Monitor

`MonitorDetails` در تحلیل اولیه می‌تواند وجود داشته باشد، اما منطق ثبت نهایی آن را به asset نوع display منتقل می‌کند.

این تصمیم مانع از دو بار ثبت یک نمایشگر به‌عنوان peripheral کامپیوتر می‌شود.

## 8. Printer

اطلاعات اختصاصی چاپگر در asset نوع printer قرار می‌گیرد.

MAC برای printer/scanner از CSV وارد نمی‌شود؛ IP در فرم قابل تکمیل دستی است.

## 9. Scanner

فیلدهای نوع اسکنر، resolution، connection و duplex در asset نوع scanner قرار می‌گیرند.

## 10. CollectedAt

CollectedAt باید به‌عنوان زمان جمع‌آوری اطلاعات ابزار ثبت شود. در usage_stats با نام `collected_at` ذخیره می‌شود و در audit نیز در details قابل ردیابی است.

## 11. تفاوت collected و registration

CollectedAt = زمان منبع داده.

registration_at = زمان ثبت نهایی توسط کاربر سامانه.

registration_ip = IP ثبت‌کننده.

created_by = شناسه کاربر.

این چهار مفهوم نباید با هم ادغام شوند.
