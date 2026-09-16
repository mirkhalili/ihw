<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require_login();

$no = preg_replace('/[^0-9]/', '', (string)($_GET['asset_no'] ?? ''));
if (!preg_match('/^\d{7}$/', $no)) { http_response_code(400); exit('شماره اموال نامعتبر است.'); }

$st = db()->prepare('SELECT a.*, p.full_name personnel_name, p.personnel_code FROM assets a LEFT JOIN personnel p ON p.id=a.personnel_id WHERE a.asset_no=?');
$st->execute([$no]);
$asset = $st->fetch();
if (!$asset) { http_response_code(404); exit('تجهیز یافت نشد.'); }

$st = db()->prepare('SELECT * FROM asset_disks WHERE asset_no=? ORDER BY disk_index');
$st->execute([$no]);
$disks = $st->fetchAll();
$st = db()->prepare('SELECT * FROM asset_ram_slots WHERE asset_no=? ORDER BY slot_no');
$st->execute([$no]);
$rams = $st->fetchAll();

$tech = json_decode((string)$asset['technical'], true);
$tech = is_array($tech) ? $tech : [];
$types = ['computer'=>'رایانه','printer'=>'چاپگر','display'=>'نمایشگر','scanner'=>'اسکنر'];
$statusLabels = ['active'=>'فعال','repair'=>'تعمیر','retired'=>'اسقاط','lost'=>'مفقود'];
$labels = [
    'ComputerName'=>'نام کامپیوتر','UserName'=>'کاربر واردشده','Manufacturer'=>'سازنده','Model'=>'مدل','SystemType'=>'نوع سیستم','SystemSerial'=>'سریال سیستم','SystemUUID'=>'UUID سیستم','Domain'=>'دامنه / Workgroup',
    'OSName'=>'نام سیستم‌عامل','OSVersion'=>'نسخه سیستم‌عامل','OSBuild'=>'Build سیستم‌عامل','OSArchitecture'=>'معماری سیستم‌عامل',
    'CPUName'=>'پردازنده','CPUManufacturer'=>'سازنده CPU','CPUCores'=>'هسته فیزیکی','CPULogicalProcessors'=>'پردازنده منطقی','CPUMaxClockMHz'=>'حداکثر فرکانس (MHz)',
    'RAMTotalGB'=>'RAM کل (GB)','RAMSlotCount'=>'تعداد اسلات RAM','RAMInstalledSlots'=>'اسلات‌های پر','RAMType'=>'نوع RAM','RAMManufacturers'=>'سازنده‌های RAM','RAMPartNumbers'=>'Part Numberهای RAM','RAMSerialNumbers'=>'سریال‌های RAM','RAMSpeedsMHz'=>'فرکانس RAM (MHz)','RAMSlotDetails'=>'جزئیات اسلات‌های RAM',
    'MotherboardManufacturer'=>'سازنده مادربرد','MotherboardProduct'=>'مدل مادربرد','MotherboardVersion'=>'نسخه مادربرد','MotherboardSerial'=>'سریال مادربرد','MotherboardChipset'=>'چیپست','MotherboardMaxRAMGB'=>'حداکثر RAM (GB)',
    'BIOSManufacturer'=>'سازنده BIOS','BIOSVersion'=>'نسخه BIOS','BIOSSerial'=>'سریال BIOS','BIOSReleaseDate'=>'تاریخ BIOS',
    'LogicalDriveDetails'=>'درایوهای منطقی','NetworkDetails'=>'کارت‌های شبکه','IPAddresses'=>'آدرس‌های IP','MACAddresses'=>'آدرس‌های MAC','SubnetMasks'=>'Subnet Mask','Gateways'=>'Gateway','DNSServers'=>'DNS',
    'GPUDetails'=>'کارت گرافیک','SoundDetails'=>'کارت صدا','MonitorDetails'=>'مانیتور','PageFileDetails'=>'Page File','AntivirusDetails'=>'آنتی‌ویروس',
    'BootCount'=>'تعداد بوت','NormalShutdownCount'=>'خاموشی عادی','UnexpectedShutdownCount'=>'خاموشی غیرمنتظره','UserShutdownCount'=>'خاموشی توسط کاربر','LastBootTime'=>'آخرین Boot','LastShutdownTime'=>'آخرین Shutdown',
    'CompletedSessionCount'=>'جلسات کامل‌شده','CurrentSessionHours'=>'جلسه فعلی (ساعت)','CurrentSessionDuration'=>'مدت جلسه فعلی','TotalUsageHours'=>'کل استفاده (ساعت)','TotalUsageDuration'=>'کل مدت استفاده','AverageSessionHours'=>'میانگین جلسه (ساعت)','AverageSessionDuration'=>'میانگین مدت جلسه','LongestSessionHours'=>'طولانی‌ترین جلسه (ساعت)','LongestSessionDuration'=>'مدت طولانی‌ترین جلسه','CollectedAt'=>'زمان جمع‌آوری',
    'PrinterType'=>'نوع چاپگر','PrinterCapabilities'=>'قابلیت‌ها','ColorMode'=>'رنگی / سیاه‌وسفید','Connection'=>'نوع اتصال','NetworkSharing'=>'اشتراک‌گذاری شبکه','Duplex'=>'چاپ دورو','DisplayTechnology'=>'فناوری نمایشگر','DisplaySize'=>'اندازه نمایشگر','DisplayResolution'=>'رزولوشن نمایشگر','DisplaySerial'=>'سریال نمایشگر','ScannerType'=>'نوع اسکنر','ScannerResolution'=>'رزولوشن اسکنر','ScannerConnection'=>'اتصال اسکنر','ScannerDuplex'=>'اسکن دورو'
];
$arrayFields = ['IPAddresses','MACAddresses','SubnetMasks','Gateways','DNSServers','RAMManufacturers','RAMPartNumbers','RAMSerialNumbers','RAMSpeedsMHz','DiskModels','DiskSizesGB','DiskSerials','DiskInterfaces'];
$sections = [
    'computer' => [
        'سیستم و کاربر'=>['ComputerName','UserName','Manufacturer','Model','SystemType','SystemSerial','SystemUUID','Domain'],
        'سیستم‌عامل'=>['OSName','OSVersion','OSBuild','OSArchitecture'],
        'پردازنده'=>['CPUName','CPUManufacturer','CPUCores','CPULogicalProcessors','CPUMaxClockMHz'],
        'مادربرد'=>['MotherboardManufacturer','MotherboardProduct','MotherboardVersion','MotherboardSerial','MotherboardChipset','MotherboardMaxRAMGB'],
        'BIOS'=>['BIOSManufacturer','BIOSVersion','BIOSSerial','BIOSReleaseDate'],
        'شبکه'=>['NetworkDetails','IPAddresses','MACAddresses','SubnetMasks','Gateways','DNSServers'],
        'تجهیزات جانبی'=>['GPUDetails','SoundDetails','MonitorDetails','PageFileDetails','AntivirusDetails'],
        'پایش روشن/خاموش و استفاده'=>['BootCount','NormalShutdownCount','UnexpectedShutdownCount','UserShutdownCount','LastBootTime','LastShutdownTime','CompletedSessionCount','CurrentSessionHours','CurrentSessionDuration','TotalUsageHours','TotalUsageDuration','AverageSessionHours','AverageSessionDuration','LongestSessionHours','LongestSessionDuration','CollectedAt']
    ],
    'printer'=>['مشخصات چاپگر'=>['PrinterType','PrinterCapabilities','ColorMode','Connection','NetworkSharing','Duplex','IPAddresses']],
    'display'=>['مشخصات نمایشگر'=>['DisplayTechnology','DisplaySize','DisplayResolution','DisplaySerial','Connection']],
    'scanner'=>['مشخصات اسکنر'=>['ScannerType','ScannerResolution','ScannerConnection','ScannerDuplex','IPAddresses']]
];

function value_text(mixed $value): string {
    if (is_array($value)) return implode('، ', array_map('strval', $value));
    return trim(str_replace('|', ' | ', (string)$value));
}
function has_value(array $tech, string $key): bool {
    if (!array_key_exists($key, $tech)) return false;
    $v = $tech[$key];
    return !(is_array($v) ? count($v) === 0 : trim((string)$v) === '');
}
function list_items(mixed $value): array {
    if (is_array($value)) return array_values(array_filter(array_map('trim', array_map('strval', $value)), fn($v)=>$v!==''));
    $s=trim((string)$value); if($s==='') return [];
    return array_values(array_filter(array_map('trim', preg_split('/\s*,\s*/u',$s)?:[]),fn($v)=>$v!==''));
}
function network_items(string $value): array {
    $out=[];
    foreach (preg_split('/\s*\|\|\s*/u', trim($value)) ?: [] as $record) {
        $record=trim($record); if($record==='') continue; $item=[];
        foreach (preg_split('/\s*\/\s*/u',$record) ?: [] as $part) {
            if (strpos($part, ':')===false) continue; [$k,$v]=array_map('trim',explode(':',$part,2)); if($k!=='') $item[$k]=$v;
        }
        if($item) $out[]=$item;
    }
    return $out;
}
function technical_value(array $tech,string $key): mixed { return $tech[$key] ?? null; }
?>
<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($asset['asset_no'])?> | پرونده تجهیز</title><link rel="stylesheet" href="assets/app.css"></head><body>
<header><a class="brand" href="index.php">سامانه سخت‌افزار</a><nav><a href="index.php">داشبورد</a><a href="assets.php">تجهیزات</a><a href="report.php">گزارش‌ها</a><?php if((user()['role']??'')==='admin'):?><a href="audit.php">رویدادها</a><?php endif;?><a href="logout.php">خروج</a></nav></header>
<main>
<div class="page-head"><div><div class="eyebrow">پرونده تجهیز</div><h1><?=e($asset['asset_no'])?></h1><p class="muted"><?=e($types[$asset['asset_type']]??$asset['asset_type'])?> · آخرین بروزرسانی <?=e($asset['updated_at'])?></p></div><div class="actions"><?php if($asset['source_file']):?><a class="btn" href="source_download.php?asset_no=<?=urlencode($no)?>">CSV منبع</a><?php endif;?><?php if(can_manage_asset_type($asset['asset_type'],'edit')):?><a class="btn primary" href="asset_edit.php?asset_no=<?=urlencode($no)?>">ویرایش پرونده</a><?php endif;?></div></div>
<section class="asset-summary"><div class="asset-title"><span class="asset-icon"><?=e(mb_substr($types[$asset['asset_type']]??'تجهیز',0,1))?></span><div><strong><?=e(trim(($asset['manufacturer']??'').' '.($asset['model']??''))?:'بدون مدل')?></strong><span><?=e($asset['hostname']?:'نام دستگاه ثبت نشده')?></span></div></div><div class="summary-item"><span>مسئول / کاربر</span><b><?=e($asset['personnel_name']?:($tech['UserName']??'—'))?></b></div><div class="summary-item"><span>مکان</span><b><?=e($asset['location']?:'—')?></b></div><div class="summary-item"><span>وضعیت</span><b class="badge <?=e($asset['status'])?>"><?=e($statusLabels[$asset['status']]??$asset['status'])?></b></div></section>
<?php if($asset['asset_type']==='computer' && (int)($tech['UnexpectedShutdownCount']??0)>0):?><div class="alert warning"><strong>پایش خاموشی:</strong> تعداد <?=e((string)$tech['UnexpectedShutdownCount'])?> خاموشی غیرمنتظره در داده جمع‌آوری‌شده ثبت شده است.</div><?php endif;?>
<section class="panel"><div class="section-title"><div><h2>مشخصات پایه</h2><p class="muted">اطلاعات شناسایی، مالکیت و محل تجهیز</p></div></div><div class="info-grid"><div><span>شماره اموال</span><strong><?=e($asset['asset_no'])?></strong></div><div><span>نوع</span><strong><?=e($types[$asset['asset_type']]??$asset['asset_type'])?></strong></div><div><span>زیرنوع</span><strong><?=e($asset['device_subtype']?:'—')?></strong></div><div><span>نام دستگاه</span><strong><?=e($asset['hostname']?:($tech['ComputerName']??'—'))?></strong></div><div><span>سریال</span><strong><?=e($asset['serial_no']?:($tech['SystemSerial']??'—'))?></strong></div><div><span>پرسنل</span><strong><?=e($asset['personnel_name']?:'—')?></strong></div><div><span>کد پرسنلی</span><strong><?=e($asset['personnel_code']?:'—')?></strong></div><div><span>اداره / دانشکده</span><strong><?=e($asset['department']?:'—')?></strong></div><div><span>مکان</span><strong><?=e($asset['location']?:'—')?></strong></div></div></section>
<?php foreach(($sections[$asset['asset_type']]??[]) as $sectionTitle=>$keys): $visible=array_values(array_filter($keys,fn($k)=>has_value($tech,$k))); if(!$visible) continue; ?><section class="panel hardware-section"><div class="section-title"><h2><?=e($sectionTitle)?></h2></div><div class="technical-grid"><?php foreach($visible as $k): $v=technical_value($tech,$k); ?><div class="technical-item <?=in_array($k,$arrayFields,true)?'wide-value':''?>"><span><?=e($labels[$k]??$k)?></span><strong dir="<?=in_array($k,['CPUName','SystemUUID','IPAddresses','MACAddresses','SubnetMasks','Gateways','DNSServers'],true)?'ltr':'rtl'?>"><?php if(in_array($k,$arrayFields,true)): ?><?=e(value_text($v))?><?php else: ?><?=e(value_text($v))?><?php endif;?></strong></div><?php endforeach;?></div></section><?php endforeach;?>
<?php if($asset['asset_type']==='computer'): ?>
<section class="panel"><div class="section-title"><div><h2>حافظه RAM</h2><p class="muted">جزئیات ساختاری هر اسلات، جدا از خلاصه RAM</p></div></div><?php if($rams):?><div class="table-wrap"><table><thead><tr><th>اسلات</th><th>وضعیت</th><th>ظرفیت GB</th><th>نوع</th><th>فرکانس MHz</th><th>سازنده</th><th>Part Number</th><th>سریال</th></tr></thead><tbody><?php foreach($rams as $r):?><tr><td><?=e((string)$r['slot_no'])?></td><td><span class="badge <?=($r['state']??'')==='occupied'?'active':''?>"><?=e(['occupied'=>'نصب‌شده','empty'=>'خالی','unknown'=>'نامشخص'][$r['state']]??$r['state'])?></span></td><td><?=e((string)($r['capacity_gb']??'—'))?></td><td><?=e($r['ram_type']?:'—')?></td><td><?=e((string)($r['speed_mhz']??'—'))?></td><td><?=e($r['manufacturer']?:'—')?></td><td dir="ltr"><?=e($r['part_number']?:'—')?></td><td dir="ltr"><?=e($r['serial_no']?:'—')?></td></tr><?php endforeach;?></tbody></table></div><?php else:?><div class="empty">جزئیات RAM در جدول ساختاری ثبت نشده است.</div><?php endif;?></section>
<section class="panel"><div class="section-title"><div><h2>ذخیره‌سازی فیزیکی</h2><p class="muted">هر دیسک به‌صورت یک رکورد مستقل</p></div></div><?php if($disks):?><div class="table-wrap"><table><thead><tr><th>ردیف</th><th>مدل</th><th>ظرفیت GB</th><th>سریال</th><th>رابط</th><th>رسانه</th></tr></thead><tbody><?php foreach($disks as $d):?><tr><td><?=e((string)$d['disk_index'])?></td><td dir="ltr"><?=e($d['model']?:'—')?></td><td><?=e((string)($d['size_gb']??'—'))?></td><td dir="ltr"><?=e($d['serial_no']?:'—')?></td><td><?=e($d['interface_name']?:'—')?></td><td><?=e($d['media']?:'—')?></td></tr><?php endforeach;?></tbody></table></div><?php else:?><div class="empty">دیسک فیزیکی ثبت نشده است. در صورت وجود DiskDetails در CSV، از ویرایش پرونده برای بازبینی داده استفاده کنید.</div><?php endif;?></section>
<?php if(!empty($tech['NetworkDetails'])): $nets=network_items((string)$tech['NetworkDetails']); ?><section class="panel"><div class="section-title"><div><h2>جزئیات کارت‌های شبکه</h2><p class="muted">هر کارت شبکه بر اساس ساختار NetworkDetails</p></div></div><?php if($nets):?><div class="network-grid"><?php foreach($nets as $n):?><article class="network-card"><h3><?=e($n['Name']??'کارت شبکه')?></h3><div><span>MAC</span><b dir="ltr"><?=e($n['MAC']??'—')?></b></div><div><span>IP</span><b dir="ltr"><?=e($n['IP']??'—')?></b></div><div><span>Mask</span><b dir="ltr"><?=e($n['Mask']??'—')?></b></div><div><span>Gateway</span><b dir="ltr"><?=e($n['Gateway']??'—')?></b></div><div><span>DNS</span><b dir="ltr"><?=e($n['DNS']??'—')?></b></div><div><span>Status</span><b><?=e($n['Status']??'—')?></b></div><div><span>Physical</span><b><?=e($n['Physical']??'—')?></b></div><div><span>Speed</span><b><?=e($n['Speed']??'—')?></b></div></article><?php endforeach;?></div><?php else:?><div class="empty">NetworkDetails قابل تجزیه نبود؛ متن خام در بخش شبکه قابل مشاهده است.</div><?php endif;?></section><?php endif; ?>
<?php endif; ?>
</main></body></html>
