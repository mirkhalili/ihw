# قرارداد CSV و Import

## 1. ورودی
Importer فایل CSV را از مسیر موقت/خصوصی می‌خواند. delimiter بین comma، semicolon و tab با شمارش جداکننده‌های خط اول تشخیص داده می‌شود.

## 2. BOM
UTF-8 BOM از اولین خط حذف می‌شود.

## 3. آرایه‌ها
این فیلدها به‌عنوان list شناخته می‌شوند:
IPAddresses، MACAddresses، SubnetMasks، Gateways، DNSServers، RAMManufacturers، RAMPartNumbers، RAMSerialNumbers، RAMSpeedsMHz، DiskModels، DiskSizesGB، DiskSerials، DiskInterfaces، PrinterNames، PrinterPorts، PrinterDrivers، DuplexPrinters، ScannerNames، ScannerManufacturers.

Pipe با الگوی `|` جدا می‌کند؛ whitespace اطراف pipe حذف می‌شود.

## 4. چاپگر و اسکنر
PrinterNames/Ports/Drivers/DuplexPrinters و ScannerNames/Manufacturers به comma-list نیز normalize می‌شوند.

PrinterDetails و ScannerDetails رکوردهای خود را با `||` جدا می‌کنند.

رکوردهای Adobe از PrinterDetails حذف می‌شوند و اگر PrinterCount خالی باشد بر اساس رکوردهای باقی‌مانده محاسبه می‌شود. DefaultPrinterName شامل Adobe خالی می‌شود.

## 5. Disk
اگر DiskModels/DiskSizesGB/DiskSerials/DiskInterfaces موجود باشند، آن‌ها منبع ترجیحی هستند و به asset_disks تبدیل می‌شوند.
در غیر این صورت DiskDetails parse می‌شود.

فرمت‌های متداول:
`Model / 512 GB / Serial: ... / Interface: SATA / Media: SSD`

## 6. RAM
RAMSlotDetails با رکوردهای جداشده بر اساس comma و شروع slot/capacity تحلیل می‌شود. فیلدهای capacity، speed، type، manufacturer، part number و serial استخراج می‌شوند.

## 7. اصل import
Importer نباید داده خام را با حدس خطرناک تغییر دهد. normalize باید deterministic باشد و تغییرات semantic در همین فایل مستند شوند.

## 8. خطاهای رایج
- encoding نامناسب
- delimiter اشتباه
- pipe داخل مقدار واقعی
- تاریخ با format ناشناخته
- DiskDetails غیر استاندارد
- CSV بدون header
