<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require_role(['admin','collector','expert']);
require_once __DIR__.'/../app/Services/CsvImporter.php';

$types = ['computer'=>'کامپیوتر','printer'=>'چاپگر','scanner'=>'اسکنر','display'=>'نمایشگر'];
$steps = array_keys($types);
$step = (string)($_GET['step'] ?? $_POST['step'] ?? 'computer');
if ($step !== 'review' && !isset($types[$step])) $step = 'computer';
if ($step !== 'review' && !can_manage_asset_type($step, 'create')) { http_response_code(403); exit('برای این بخش دسترسی ثبت ندارید.'); }
$importer = new CsvImporter();
$arrayFields = CsvImporter::arrayFields();
$wizard = $_SESSION['_hardware_wizard'] ?? ['records'=>[]];
$error = null;
$usageMap = [
    'BootCount'=>'boot_count','NormalShutdownCount'=>'normal_shutdown_count','UnexpectedShutdownCount'=>'unexpected_shutdown_count','UserShutdownCount'=>'user_shutdown_count',
    'LastBootTime'=>'last_boot_time','LastShutdownTime'=>'last_shutdown_time','CompletedSessionCount'=>'completed_session_count',
    'CurrentSessionHours'=>'current_session_hours','CurrentSessionDuration'=>'current_session_duration','TotalUsageHours'=>'total_usage_hours',
    'TotalUsageDuration'=>'total_usage_duration','AverageSessionHours'=>'average_session_hours','AverageSessionDuration'=>'average_session_duration',
    'LongestSessionHours'=>'longest_session_hours','LongestSessionDuration'=>'longest_session_duration','CollectedAt'=>'collected_at'
];
$labels = [
    'ComputerName'=>'نام کامپیوتر','UserName'=>'کاربر','Manufacturer'=>'سازنده','Model'=>'مدل','SystemType'=>'نوع سیستم','SystemSerial'=>'سریال سیستم','SystemUUID'=>'UUID','Domain'=>'دامین / Workgroup',
    'OSName'=>'سیستم‌عامل','OSVersion'=>'نسخه سیستم‌عامل','OSBuild'=>'Build','OSArchitecture'=>'معماری','CPUName'=>'پردازنده','CPUManufacturer'=>'سازنده CPU','CPUCores'=>'هسته فیزیکی','CPULogicalProcessors'=>'پردازنده منطقی','CPUMaxClockMHz'=>'فرکانس CPU (MHz)',
    'RAMTotalGB'=>'RAM کل (GB)','RAMSlotCount'=>'تعداد اسلات RAM','RAMInstalledSlots'=>'اسلات‌های پر','RAMType'=>'نوع RAM','RAMSlotDetails'=>'جزئیات اسلات RAM','MotherboardManufacturer'=>'سازنده مادربرد','MotherboardProduct'=>'مدل مادربرد','MotherboardVersion'=>'نسخه مادربرد','MotherboardSerial'=>'سریال مادربرد',
    'BIOSManufacturer'=>'سازنده BIOS','BIOSVersion'=>'نسخه BIOS','BIOSSerial'=>'سریال BIOS','BIOSReleaseDate'=>'تاریخ BIOS','DiskDetails'=>'دیسک‌های فیزیکی','LogicalDriveDetails'=>'درایوهای منطقی','NetworkDetails'=>'جزئیات کامل شبکه','GPUDetails'=>'کارت گرافیک','SoundDetails'=>'صدا','PageFileDetails'=>'Page File','AntivirusDetails'=>'آنتی‌ویروس','MonitorDetails'=>'اطلاعات نمایشگر',
    'PrinterCount'=>'تعداد پرینترهای شناسایی‌شده','DefaultPrinterName'=>'پرینتر پیش‌فرض','PrinterNames'=>'نام پرینترها','PrinterPorts'=>'پورت‌های پرینترها','PrinterDrivers'=>'درایورهای پرینترها','DuplexPrinters'=>'پرینترهای دارای چاپ دورو','PrinterDetails'=>'جزئیات کامل پرینترها',
    'ScannerCount'=>'تعداد اسکنرهای شناسایی‌شده','ScannerNames'=>'نام اسکنرها','ScannerManufacturers'=>'سازندگان اسکنر','ScannerDetails'=>'جزئیات کامل اسکنرها',
    'PrinterType'=>'نوع چاپگر','PrinterCapabilities'=>'قابلیت‌ها','ColorMode'=>'رنگی/سیاه‌وسفید','Connection'=>'نوع اتصال','NetworkSharing'=>'اشتراک‌گذاری شبکه','Duplex'=>'چاپ دورو',
    'DisplayTechnology'=>'فناوری نمایشگر','DisplaySize'=>'اندازه نمایشگر','DisplayResolution'=>'رزولوشن','DisplaySerial'=>'سریال نمایشگر','ScannerType'=>'نوع اسکنر','ScannerResolution'=>'رزولوشن اسکنر','ScannerConnection'=>'اتصال اسکنر','ScannerDuplex'=>'اسکن دورو'
];
$stageFields = [
    'printer'=>['PrinterCount','DefaultPrinterName','PrinterNames','PrinterPorts','PrinterDrivers','DuplexPrinters','PrinterDetails'],
    'scanner'=>['ScannerCount','ScannerNames','ScannerManufacturers','ScannerDetails']
];
function wiz_label(string $key): string { global $labels, $usageMap; return $labels[$key] ?? $usageMap[$key] ?? $key; }
function wiz_value(mixed $value): string { return is_array($value) ? (string)json_encode($value, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) : (string)$value; }
function wiz_stage_record_is_skipped(array $record): bool { return !empty($record['_skipped']); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'reset') {
        unset($_SESSION['_hardware_wizard']);
        redirect('asset_wizard.php');
    }

    if ($step !== 'review' && $action === 'analyze') {
        try {
            if (empty($_FILES['csv']['tmp_name']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('فایل CSV انتخاب نشده است.');
            if (strtolower(pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION)) !== 'csv') throw new RuntimeException('فقط فایل CSV مجاز است.');
            $rows = $importer->rows($_FILES['csv']['tmp_name']);
            $record = $rows->current();
            if (!$record) throw new RuntimeException('فایل داده‌ای ندارد.');
            if (in_array($step, ['printer','scanner'], true)) { $record['IPAddresses'] = []; unset($record['MACAddresses']); }
            if ($step === 'computer' && !empty($record['MonitorDetails'])) $wizard['records']['display']['MonitorDetails'] = $record['MonitorDetails'];
            $record['_source_file'] = basename($_FILES['csv']['name']);
            unset($record['_skipped']);
            $wizard['records'][$step] = $record;
            $_SESSION['_hardware_wizard'] = $wizard;
            redirect('asset_wizard.php?step='.$step);
        } catch (Throwable $e) { $error = $e->getMessage(); }
    }

    if ($step !== 'review' && $action === 'skip') {
        $record = $wizard['records'][$step] ?? [];
        $wizard['records'][$step] = ['_skipped'=>true,'_source_file'=>$record['_source_file'] ?? null];
        $_SESSION['_hardware_wizard'] = $wizard;
        $index = array_search($step, $steps, true);
        redirect($index < count($steps)-1 ? 'asset_wizard.php?step='.$steps[$index+1] : 'asset_wizard.php?step=review');
    }

    if ($step !== 'review' && $action === 'next') {
        $record = $wizard['records'][$step] ?? [];
        unset($record['_skipped']);
        foreach ((array)($_POST['technical'] ?? []) as $key=>$value) {
            $text = trim((string)$value);
            if (in_array($key, $arrayFields, true)) {
                $decoded = json_decode($text, true);
                $record[$key] = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? array_values($decoded) : ($text === '' ? [] : $importer->split($text));
            } else {
                $record[$key] = $text;
            }
        }
        $record['asset_no'] = trim((string)($_POST['asset_no'] ?? ''));
        $record['location'] = trim((string)($_POST['location'] ?? ''));
        $record['personnel_id'] = trim((string)($_POST['personnel_id'] ?? ''));
        if (!preg_match('/^\d{7}$/', $record['asset_no'])) {
            $error = 'شماره اموال باید دقیقاً ۷ رقم باشد.';
        } elseif ($record['location'] === '') {
            $error = 'مکان الزامی است.';
        } else {
            $q = db()->prepare('SELECT asset_no FROM assets WHERE asset_no=?');
            $q->execute([$record['asset_no']]);
            if ($q->fetchColumn()) {
                $error = 'شماره اموال '.$record['asset_no'].' قبلاً ثبت شده است. <a href="asset_edit.php?asset_no='.rawurlencode($record['asset_no']).'">ویرایش همان تجهیز</a>';
            } else {
                $wizard['records'][$step] = $record;
                $_SESSION['_hardware_wizard'] = $wizard;
                $index = array_search($step, $steps, true);
                redirect($index < count($steps)-1 ? 'asset_wizard.php?step='.$steps[$index+1] : 'asset_wizard.php?step=review');
            }
        }
    }

    if ($step === 'review' && $action === 'finish') {
        try {
            $activeSteps = [];
            foreach ($steps as $type) {
                $record = $wizard['records'][$type] ?? [];
                if (wiz_stage_record_is_skipped($record)) continue;
                if (empty($record['asset_no'])) throw new RuntimeException('مرحله '.$types[$type].' تکمیل نشده است یا باید از آن رد شوید.');
                $activeSteps[] = $type;
            }
            if (!$activeSteps) throw new RuntimeException('حداقل یک تجهیز باید ثبت شود.');
            $seen = [];
            foreach ($activeSteps as $type) {
                $assetNo = $wizard['records'][$type]['asset_no'];
                if (!preg_match('/^\d{7}$/', $assetNo)) throw new RuntimeException('شماره اموال '.$types[$type].' نامعتبر است.');
                if (isset($seen[$assetNo])) throw new RuntimeException('شماره اموال '.$assetNo.' در دو مرحله تکرار شده است.');
                $seen[$assetNo] = true;
                $q = db()->prepare('SELECT asset_no FROM assets WHERE asset_no=?');
                $q->execute([$assetNo]);
                if ($q->fetchColumn()) throw new RuntimeException('شماره اموال '.$assetNo.' قبلاً ثبت شده است؛ <a href="asset_edit.php?asset_no='.rawurlencode($assetNo).'">ویرایش</a>');
            }
            $db = db();
            $db->beginTransaction();
            foreach ($activeSteps as $type) {
                $record = $wizard['records'][$type];
                $technical = [];
                foreach ($record as $key=>$value) {
                    if ($key === '' || $key[0] === '_' || isset($usageMap[$key]) || in_array($key, ['asset_no','location','personnel_id'], true)) continue;
                    if ($type === 'computer' && in_array($key, ['IPAddresses','MACAddresses','SubnetMasks','Gateways','DNSServers','MonitorDetails'], true)) continue;
                    if (isset($stageFields[$type]) && !in_array($key, $stageFields[$type], true)) continue;
                    $technical[$key] = $value;
                }
                if ($type === 'display' && isset($wizard['records']['display']['MonitorDetails'])) $technical['MonitorDetails'] = $wizard['records']['display']['MonitorDetails'];
                $hostname = $record['ComputerName'] ?? $record['PrinterName'] ?? $record['ScannerName'] ?? $record['DisplayName'] ?? null;
                $manufacturer = $record['Manufacturer'] ?? $record['PrinterManufacturer'] ?? $record['ScannerManufacturer'] ?? $record['DisplayManufacturer'] ?? '';
                $model = $record['Model'] ?? $record['PrinterModel'] ?? $record['ScannerModel'] ?? $record['DisplayModel'] ?? '';
                $serial = $record['SystemSerial'] ?? $record['PrinterSerial'] ?? $record['ScannerSerial'] ?? $record['DisplaySerial'] ?? '';
                $q = $db->prepare('INSERT INTO assets(asset_no,asset_type,device_subtype,hostname,personnel_id,location,manufacturer,model,serial_no,technical,status,source_file,created_by,updated_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                $q->execute([$record['asset_no'],$type,$record['device_subtype'] ?? null,$hostname,($record['personnel_id'] ?? '') !== '' ? (int)$record['personnel_id'] : null,$record['location'],$manufacturer,$model,$serial,json_encode($technical,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE),'active',$record['_source_file'] ?? null,user()['id'],user()['id']]);
                if ($type === 'computer') {
                    $usage = [];
                    foreach ($usageMap as $source=>$column) $usage[$column] = (($record[$source] ?? '') === '') ? null : $record[$source];
                    try {
                        $q = $db->prepare('INSERT INTO usage_stats(asset_no,boot_count,normal_shutdown_count,unexpected_shutdown_count,user_shutdown_count,last_boot_time,last_shutdown_time,completed_session_count,current_session_hours,current_session_duration,total_usage_hours,total_usage_duration,average_session_hours,average_session_duration,longest_session_hours,longest_session_duration,collected_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE boot_count=VALUES(boot_count),normal_shutdown_count=VALUES(normal_shutdown_count),unexpected_shutdown_count=VALUES(unexpected_shutdown_count),user_shutdown_count=VALUES(user_shutdown_count),last_boot_time=VALUES(last_boot_time),last_shutdown_time=VALUES(last_shutdown_time),completed_session_count=VALUES(completed_session_count),current_session_hours=VALUES(current_session_hours),current_session_duration=VALUES(current_session_duration),total_usage_hours=VALUES(total_usage_hours),total_usage_duration=VALUES(total_usage_duration),average_session_hours=VALUES(average_session_hours),average_session_duration=VALUES(average_session_duration),longest_session_hours=VALUES(longest_session_hours),longest_session_duration=VALUES(longest_session_duration),collected_at=VALUES(collected_at)');
                        $q->execute([$record['asset_no'],$usage['boot_count'],$usage['normal_shutdown_count'],$usage['unexpected_shutdown_count'],$usage['user_shutdown_count'],$usage['last_boot_time'],$usage['last_shutdown_time'],$usage['completed_session_count'],$usage['current_session_hours'],$usage['current_session_duration'],$usage['total_usage_hours'],$usage['total_usage_duration'],$usage['average_session_hours'],$usage['average_session_duration'],$usage['longest_session_hours'],$usage['longest_session_duration'],$usage['collected_at']]);
                    } catch (PDOException $usageError) {
                        if ($usageError->getCode() !== '42S02') throw $usageError;
                        $q = $db->prepare('UPDATE assets SET boot_count=?,normal_shutdown_count=?,unexpected_shutdown_count=?,user_shutdown_count=?,last_boot_time=?,last_shutdown_time=?,completed_session_count=?,current_session_hours=?,current_session_duration=?,total_usage_hours=?,total_usage_duration=?,average_session_hours=?,average_session_duration=?,longest_session_hours=?,longest_session_duration=?,collected_at=? WHERE asset_no=?');
                        $q->execute([$usage['boot_count'],$usage['normal_shutdown_count'],$usage['unexpected_shutdown_count'],$usage['user_shutdown_count'],$usage['last_boot_time'],$usage['last_shutdown_time'],$usage['completed_session_count'],$usage['current_session_hours'],$usage['current_session_duration'],$usage['total_usage_hours'],$usage['total_usage_duration'],$usage['average_session_hours'],$usage['average_session_duration'],$usage['longest_session_hours'],$usage['longest_session_duration'],$usage['collected_at'],$record['asset_no']]);
                    }
                }
                foreach ($importer->disks($record) as $disk) {
                    $q = $db->prepare('INSERT INTO asset_disks(asset_no,disk_index,model,size_gb,serial_no,interface_name,media) VALUES(?,?,?,?,?,?,?)');
                    $q->execute([$record['asset_no'],$disk['index'],$disk['model'],$disk['size_gb'],$disk['serial_no'],$disk['interface'] ?? null,$disk['media'] ?? null]);
                }
                foreach ($importer->ramSlots($record) as $ram) {
                    $q = $db->prepare('INSERT INTO asset_ram_slots(asset_no,slot_no,state,capacity_gb,ram_type,speed_mhz,manufacturer,part_number,serial_no) VALUES(?,?,?,?,?,?,?,?,?)');
                    $q->execute([$record['asset_no'],$ram['slot_no'],$ram['state'] ?? 'unknown',$ram['capacity_gb'] ?? null,$ram['ram_type'] ?? null,$ram['speed_mhz'] ?? null,$ram['manufacturer'] ?? null,$ram['part_number'] ?? null,$ram['serial_no'] ?? null]);
                }
                audit('hardware_wizard_asset_created','asset',$record['asset_no'],['type'=>$type,'source'=>$record['_source_file'] ?? null]);
            }
            $db->commit();
            unset($_SESSION['_hardware_wizard']);
            flash('success','تجهیزات انتخاب‌شده با موفقیت ثبت شدند. مراحل ردشده ثبت نشدند.');
            redirect('index.php');
        } catch (Throwable $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            $error = $e->getMessage();
        }
    }
}

$wizard = $_SESSION['_hardware_wizard'] ?? $wizard;
$record = $wizard['records'][$step] ?? [];
$people = db()->query('SELECT id,full_name,personnel_code FROM personnel ORDER BY full_name LIMIT 5000')->fetchAll();
?><!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ثبت مرحله‌ای تجهیزات</title><link rel="stylesheet" href="assets/app.css"></head><body><header><a class="brand" href="index.php">HWiNFO · پایش سخت‌افزار</a><nav><a href="index.php">داشبورد</a><a href="assets.php">تجهیزات</a><a href="report.php">گزارش‌ها</a><a href="logout.php">خروج</a></nav></header><main><div class="page-head"><div><div class="eyebrow">ثبت یکپارچه چهار بخش</div><h1><?=$step==='review'?'تأیید نهایی':'تحلیل '.$types[$step]?></h1><p class="muted">هر مرحله را می‌توان تکمیل یا در صورت نبود تجهیز، رد کرد.</p></div></div><div class="wizard-steps"><?php foreach($steps as $item):$x=$wizard['records'][$item]??[];?><a class="wizard-step <?=isset($x['_skipped'])?'skipped':''?> <?=isset($wizard['records'][$item])&&!isset($x['_skipped'])?'done':''?> <?=$step===$item?'active':''?>" href="asset_wizard.php?step=<?=$item?>"><b><?=array_search($item,$steps,true)+1?></b><span><?=$types[$item]?><?=isset($x['_skipped'])?' · رد شده':''?></span></a><?php endforeach;?><a class="wizard-step <?=$step==='review'?'active':''?>" href="asset_wizard.php?step=review"><b>✓</b><span>تأیید نهایی</span></a></div><?php if($error):?><div class="alert danger"><?=$error?></div><?php endif;?><?php if($step==='review'):?><section class="review-grid"><?php foreach($steps as $item):$x=$wizard['records'][$item]??[];?><article class="panel review-card"><div class="section-title"><h2><?=$types[$item]?></h2><a class="btn small" href="asset_wizard.php?step=<?=$item?>">ویرایش</a></div><?php if(wiz_stage_record_is_skipped($x)):?><p class="badge">این مرحله رد شده است و تجهیزی از این بخش ثبت نمی‌شود.</p><?php else:?><div class="review-fields"><div><span>شماره اموال</span><strong><?=e($x['asset_no']??'—')?></strong></div><div><span>مدل</span><strong><?=e($x['Model']??$x['PrinterModel']??$x['ScannerModel']??$x['DisplayModel']??'—')?></strong></div><div><span>مکان</span><strong><?=e($x['location']??'—')?></strong></div><div><span>کاربر</span><strong><?=e($x['personnel_id']??'—')?></strong></div><div><span>فایل</span><strong><?=e($x['_source_file']??'—')?></strong></div></div><?php endif;?></article><?php endforeach;?></section><form method="post"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="step" value="review"><div class="actions sticky-confirm"><button class="btn primary" name="action" value="finish">✓ تأیید نهایی و ثبت تجهیزات موجود</button><button class="btn" name="action" value="reset">شروع مجدد</button></div></form><?php else: ?><?php if(wiz_stage_record_is_skipped($record)):?><section class="panel"><div class="alert">این مرحله قبلاً رد شده است.</div><form method="post"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="step" value="<?=$step?>"><button class="btn primary" name="action" value="next">ادامه به مرحله بعد</button><button class="btn" name="action" value="reset">شروع مجدد</button></form></section><?php else: ?><section class="panel"><h2>۱. بارگذاری و تحلیل <?=$types[$step]?></h2><form method="post" enctype="multipart/form-data" class="upload-row"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="step" value="<?=$step?>"><input type="hidden" name="action" value="analyze"><input type="file" name="csv" accept=".csv,text/csv"><button class="btn" type="submit">تحلیل CSV</button></form><?php if(isset($record['_source_file'])):?><p class="muted">فایل تحلیل‌شده: <?=e($record['_source_file'])?></p><?php endif;?><p class="muted">اگر این نوع تجهیز در سیستم وجود ندارد، می‌توانید از این مرحله رد شوید.</p></section><form method="post" id="wizard-form"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="step" value="<?=$step?>"><section class="panel"><h2>۲. اطلاعات دستی</h2><div class="form-grid"><label>شماره اموال *<input id="asset-no" name="asset_no" value="<?=e($record['asset_no']??'')?>" inputmode="numeric" pattern="\d{7}" maxlength="7" required><div id="duplicate-check" class="duplicate-check"></div></label><label>مکان *<input name="location" value="<?=e($record['location']??'')?>" required></label><?php if($step==='computer'||$step==='printer'||$step==='scanner'):?><label>کاربر / مسئول<select name="personnel_id"><option value="">انتخاب پرسنل</option><?php foreach($people as $person):?><option value="<?=$person['id']?>" <?=((string)($record['personnel_id']??'')===(string)$person['id'])?'selected':''?>><?=e($person['full_name'].' — '.($person['personnel_code']??''))?></option><?php endforeach;?></select></label><?php endif;?></div><h2>۳. فیلدهای استخراج‌شده از CSV</h2><div class="form-grid"><?php foreach($record as $key=>$value):if($key===''||$key[0]==='_'||in_array($key,['asset_no','location','personnel_id','Manufacturer','Model','SystemSerial'],true)||isset($usageMap[$key])||($step==='computer'&&in_array($key,['IPAddresses','MACAddresses','SubnetMasks','Gateways','DNSServers','MonitorDetails'],true)))continue;if(isset($stageFields[$step])&&!in_array($key,$stageFields[$step],true))continue;?><label><?=e(wiz_label($key))?><textarea name="technical[<?=e($key)?>]" rows="3" <?=is_array($value)?'dir="ltr"':''?>><?=e(wiz_value($value))?></textarea></label><?php endforeach;?><?php if($step==='display'&&isset($record['MonitorDetails'])):?><label>اطلاعات نمایشگر استخراج‌شده<textarea name="technical[MonitorDetails]" rows="4"><?=e(wiz_value($record['MonitorDetails']))?></textarea></label><?php endif;?></div><?php if($step==='computer'):?><h2>۴. آمار استفاده</h2><div class="form-grid"><?php foreach($usageMap as $source=>$column):if(!array_key_exists($source,$record))continue;?><label><?=e(wiz_label($source))?><input name="technical[<?=e($source)?>]" value="<?=e((string)$record[$source])?>"></label><?php endforeach;?></div><?php endif;?><div class="actions"><button class="btn primary" id="next-button" name="action" value="next">ذخیره مرحله و ادامه</button><button class="btn" name="action" value="skip" formnovalidate>رد شدن از این مرحله</button><a class="btn" href="index.php">انصراف</a></div></section></form><script>const n=document.getElementById('asset-no'),box=document.getElementById('duplicate-check'),next=document.getElementById('next-button');let timer;async function checkAsset(){if(!n)return;const v=n.value.trim();box.classList.remove('show');box.textContent='';next.disabled=false;if(!/^\d{7}$/.test(v))return;try{const r=await fetch('asset_check.php?asset_no='+encodeURIComponent(v),{credentials:'same-origin'}),d=await r.json();if(d.duplicate){box.innerHTML='<span>⚠️ شماره اموال تکراری است.</span><a class="btn small" href="'+d.edit_url+'">ویرایش همان تجهیز</a>';box.classList.add('show');next.disabled=true;}}catch(e){}}if(n){n.addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(checkAsset,250)});n.addEventListener('blur',checkAsset);if(n.value)checkAsset();}</script><?php endif;?><?php endif;?></main></body></html>