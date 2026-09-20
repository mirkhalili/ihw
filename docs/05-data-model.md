# مدل داده و قرارداد اطلاعات

## اصل مرکزی
`assets` رکورد اصلی هر تجهیز است و `technical` محل نگهداری داده‌های متغیر نوع تجهیز است.

## کامپیوتر
اطلاعات پایه در assets؛ جزئیات تخصصی در technical؛ Disk در asset_disks؛ RAM در asset_ram_slots؛ آمار استفاده در usage_stats.

فیلدهای مهم CSV شامل:
ComputerName، UserName، Manufacturer، Model، SystemType، SystemSerial، SystemUUID، Domain، OSName، OSVersion، OSBuild، OSArchitecture، CPUName، CPUManufacturer، CPUCores، CPULogicalProcessors، CPUMaxClockMHz، RAMTotalGB، RAMSlotCount، RAMInstalledSlots، RAMType، RAMSlotDetails، اطلاعات motherboard/BIOS، DiskDetails، LogicalDriveDetails، NetworkDetails، GPUDetails، SoundDetails، MonitorDetails، PageFileDetails، AntivirusDetails و شاخص‌های usage.

## چاپگر
اطلاعات استخراج‌شده شامل PrinterCount، DefaultPrinterName، PrinterNames، PrinterPorts، PrinterDrivers، DuplexPrinters و PrinterDetails است. شماره اموال و مکان دستی هستند.

## اسکنر
ScannerCount، ScannerNames، ScannerManufacturers و ScannerDetails از CSV استخراج می‌شوند.

## نمایشگر
MonitorDetails و فیلدهای DisplayTechnology، DisplaySize، DisplayResolution، DisplaySerial، Connection در پایه display قرار می‌گیرند.

## مسئول
personnel_id به personnel اشاره می‌کند؛ نام شخص از جدول personnel انتخاب می‌شود و نباید به‌عنوان مرجع اصلی فقط متن آزاد باشد.

## JSON
فیلدهای آرایه‌ای CSV در technical به شکل JSON array ذخیره می‌شوند. هنگام نمایش، آرایه‌ها به متن قابل خواندن تبدیل می‌شوند.

## تاریخ‌ها
در assets ستون‌های legacy usage در migration 003 به VARCHAR نگهداری می‌شوند تا داده خام import از بین نرود. usage_stats نسخه ساختاریافته DATETIME را نگهداری می‌کند. بنابراین تبدیل تاریخ باید با فرمت واقعی CSV سازگار باشد.

## وضعیت تجهیز
status یکی از active، repair، retired، lost است.

## اصل سازگاری
هر فیلد جدید باید یکی از این تصمیم‌ها را داشته باشد:
1. ستون relational جدید
2. technical JSON
3. جدول فرعی
4. حذف از قرارداد داده با migration
تصمیم باید در docs ثبت شود.
