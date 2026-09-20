<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/Services/CsvImporter.php';
require_role(['admin','collector','expert']);

$types=['computer'=>'کامپیوتر','printer'=>'چاپگر','scanner'=>'اسکنر','display'=>'نمایشگر'];
$steps=array_keys($types); $step=(string)($_GET['step']??$_POST['step']??'computer');
if($step!=='review'&&!isset($types[$step]))$step='computer';
if($step!=='review'&&!can_manage_asset_type($step,'create')){http_response_code(403);exit('برای این بخش دسترسی ثبت ندارید.');}
$importer=new CsvImporter();$arrayFields=CsvImporter::arrayFields();$error=null;
$usageMap=['BootCount'=>'boot_count','NormalShutdownCount'=>'normal_shutdown_count','UnexpectedShutdownCount'=>'unexpected_shutdown_count','UserShutdownCount'=>'user_shutdown_count','LastBootTime'=>'last_boot_time','LastShutdownTime'=>'last_shutdown_time','CompletedSessionCount'=>'completed_session_count','CurrentSessionHours'=>'current_session_hours','CurrentSessionDuration'=>'current_session_duration','TotalUsageHours'=>'total_usage_hours','TotalUsageDuration'=>'total_usage_duration','AverageSessionHours'=>'average_session_hours','AverageSessionDuration'=>'average_session_duration','LongestSessionHours'=>'longest_session_hours','LongestSessionDuration'=>'longest_session_duration','CollectedAt'=>'collected_at'];
$labels=['ComputerName'=>'نام کامپیوتر','UserName'=>'کاربر','Manufacturer'=>'سازنده','Model'=>'مدل','SystemType'=>'نوع سیستم','SystemSerial'=>'سریال سیستم','SystemUUID'=>'UUID','Domain'=>'دامین / Workgroup','OSName'=>'سیستم‌عامل','OSVersion'=>'نسخه سیستم‌عامل','OSBuild'=>'Build','OSArchitecture'=>'معماری','CPUName'=>'پردازنده','CPUManufacturer'=>'سازنده CPU','CPUCores'=>'هسته فیزیکی','CPULogicalProcessors'=>'پردازنده منطقی','CPUMaxClockMHz'=>'فرکانس CPU','RAMTotalGB'=>'RAM کل','RAMSlotCount'=>'تعداد اسلات RAM','RAMInstalledSlots'=>'اسلات‌های پر','RAMType'=>'نوع RAM','RAMSlotDetails'=>'جزئیات RAM','MotherboardManufacturer'=>'سازنده مادربرد','MotherboardProduct'=>'مدل مادربرد','MotherboardVersion'=>'نسخه مادربرد','MotherboardSerial'=>'سریال مادربرد','BIOSManufacturer'=>'سازنده BIOS','BIOSVersion'=>'نسخه BIOS','BIOSSerial'=>'سریال BIOS','BIOSReleaseDate'=>'تاریخ BIOS','DiskDetails'=>'دیسک‌ها','LogicalDriveDetails'=>'درایوهای منطقی','NetworkDetails'=>'جزئیات کامل شبکه','GPUDetails'=>'کارت گرافیک','SoundDetails'=>'صدا','PageFileDetails'=>'Page File','AntivirusDetails'=>'آنتی‌ویروس','MonitorDetails'=>'اطلاعات نمایشگر','PrinterCount'=>'تعداد چاپگر','DefaultPrinterName'=>'چاپگر پیش‌فرض','PrinterNames'=>'نام چاپگر','PrinterPorts'=>'پورت','PrinterDrivers'=>'درایور','DuplexPrinters'=>'چاپ دورو','PrinterIPAddresses'=>'IP چاپگر','PrinterMACAddresses'=>'MAC چاپگر','PrinterDetails'=>'جزئیات چاپگر','PrinterManufacturer'=>'سازنده چاپگر','PrinterModel'=>'مدل چاپگر','PrinterSerial'=>'سریال چاپگر','ScannerCount'=>'تعداد اسکنر','ScannerNames'=>'نام اسکنر','ScannerManufacturers'=>'سازنده اسکنر','ScannerDetails'=>'جزئیات اسکنر','PrinterType'=>'نوع چاپگر','PrinterCapabilities'=>'قابلیت‌ها','ColorMode'=>'رنگی/سیاه‌وسفید','Connection'=>'اتصال','NetworkSharing'=>'اشتراک شبکه','Duplex'=>'چاپ دورو','DisplayTechnology'=>'فناوری نمایشگر','DisplaySize'=>'اندازه نمایشگر','DisplayResolution'=>'رزولوشن','DisplaySerial'=>'سریال نمایشگر','ScannerType'=>'نوع اسکنر','ScannerResolution'=>'رزولوشن اسکنر','ScannerConnection'=>'اتصال اسکنر','ScannerDuplex'=>'اسکن دورو'];
$stageFields=['printer'=>['PrinterCount','DefaultPrinterName','PrinterNames','PrinterPorts','PrinterDrivers','DuplexPrinters','PrinterDetails','PrinterManufacturer','PrinterModel','PrinterSerial','PrinterIPAddresses','PrinterMACAddresses','PrinterType','PrinterCapabilities','ColorMode','Connection','NetworkSharing','Duplex'],'scanner'=>['ScannerCount','ScannerNames','ScannerManufacturers','ScannerDetails','ScannerType','ScannerResolution','ScannerConnection','ScannerDuplex']];
function wl(string $k):string{global $labels,$usageMap;return $labels[$k]??$usageMap[$k]??$k;}
function wv(mixed $v):string{return is_array($v)?(string)json_encode($v,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT):(string)$v;}
function parseDateTimeValue(mixed $v):?string{
 $s=trim((string)$v);if($s==='')return null;
 $formats=['!n/j/Y g:i:s A','!n/j/Y h:i:s A','!m/d/Y g:i:s A','!m/d/Y h:i:s A','!Y-m-d H:i:s','!Y-m-d\TH:i:s'];
 foreach($formats as $f){$d=DateTimeImmutable::createFromFormat($f,$s);if($d&&DateTimeImmutable::getLastErrors()===false||$d&&is_array(DateTimeImmutable::getLastErrors())&&DateTimeImmutable::getLastErrors()['warning_count']===0&&DateTimeImmutable::getLastErrors()['error_count']===0)return $d->format('Y-m-d H:i:s');}
 $ts=strtotime($s);return $ts===false?null:date('Y-m-d H:i:s',$ts);
}
function stageRecords(array &$w,string $type):array{
 $v=$w['records'][$type]??[];if(!$v)return [];
 if(isset($v['_skipped']))return [];
 return isset($v[0])&&is_array($v[0])?$v:[$v];
}
function splitHardwareRecords(array $row,string $type,CsvImporter $importer):array{
 if($type==='computer')return [$row];
 if($type==='printer'){
  $details=trim((string)($row['PrinterDetails']??''));$parts=array_values(array_filter(array_map('trim',preg_split('/\s*\|\|\s*/u',$details)?:[])));
  $printerLists=[];
  foreach(['PrinterNames','PrinterPorts','PrinterDrivers','DuplexPrinters','PrinterIPAddresses','PrinterMACAddresses'] as $k){
   $v=$row[$k]??[];$printerLists[$k]=is_array($v)?array_values($v):($v!==''?[$v]:[]);
  }
  $count=max(count($parts),...array_map('count',$printerLists));
  if($count===0)return [];
  $out=[];
  for($i=0;$i<$count;$i++){
   $r=$row;
   $part=$parts[$i]??'';
   $name=trim((string)($printerLists['PrinterNames'][$i]??''));
   if($name==='')$name=trim((string)preg_split('/\s*\/\s*/u',$part,2)[0]);
   $r['PrinterDetails']=$part;
   $r['PrinterNames']=$name!==''?[$name]:[];
   foreach($printerLists as $k=>$list)$r[$k]=isset($list[$i])?[$list[$i]]:[];
   $r['PrinterCount']=1;$r['DefaultPrinterName']=$name;
   $r['PrinterName']=$name;
   $r['PrinterManufacturer']=$r['PrinterManufacturer']??$r['Manufacturer']??'';
   $r['PrinterModel']=$r['PrinterModel']??$r['Model']??'';
   $r['PrinterSerial']=$r['PrinterSerial']??$r['SystemSerial']??'';
   $r['_unit_index']=$i+1;$out[]=$r;
  }
  return $out;
 }
 if($type==='scanner'){
  $details=trim((string)($row['ScannerDetails']??''));$parts=array_values(array_filter(array_map('trim',preg_split('/\s*\|\|\s*/u',$details)?:[])));
  if(!$parts){$names=$row['ScannerNames']??[];$parts=is_array($names)?$names:[];}
  if(!$parts)return [];
  $out=[];foreach($parts as $i=>$part){$r=$row;$r['ScannerDetails']=$part;$name=trim((string)preg_split('/\s*\/\s*/u',$part,2)[0]);$r['ScannerNames']=$name!==''?[$name]:[];$r['ScannerCount']=1;$r['_unit_index']=$i+1;$out[]=$r;}return $out;
 }
 $details=trim((string)($row['MonitorDetails']??''));$parts=array_values(array_filter(array_map('trim',preg_split('/\s*\|\|\s*/u',$details)?:[])));
 if(!$parts&&$details!=='')$parts=[$details];if(!$parts)return [];$out=[];foreach($parts as $i=>$part){$r=$row;$r['MonitorDetails']=$part;$r['_unit_index']=$i+1;$out[]=$r;}return $out;
}
$wizard=$_SESSION['_hardware_wizard']??['records'=>[]];
$visibleSteps=array_values(array_filter($steps,function(string $x)use($wizard){return $x==='computer'||!empty(stageRecords($wizard,$x));}));
if(!empty($wizard['analysis']) && empty($wizard['records']['computer'])){$wizard['records']['computer']=[$wizard['analysis']];}
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$action=(string)($_POST['action']??'');
 if($action==='reset'){unset($_SESSION['_hardware_wizard']);redirect('asset_wizard.php');}
 if($step!=='review'&&$action==='analyze'){
  try{
   if(empty($_FILES['csv']['tmp_name'])||$_FILES['csv']['error']!==UPLOAD_ERR_OK)throw new RuntimeException('فایل CSV انتخاب نشده است.');
   if(strtolower(pathinfo($_FILES['csv']['name'],PATHINFO_EXTENSION))!=='csv')throw new RuntimeException('فقط CSV مجاز است.');
   $rows=iterator_to_array($importer->rows($_FILES['csv']['tmp_name']),false);
   if(!$rows)throw new RuntimeException('CSV داده‌ای ندارد.');
   $row=$rows[0];
   if($step==='computer'){
    $wizard['analysis']=$row;
    $wizard['records']['computer']=[$row];
    $wizard['records']['printer']=splitHardwareRecords($row,'printer',$importer);
    $wizard['records']['scanner']=splitHardwareRecords($row,'scanner',$importer);
    $wizard['records']['display']=splitHardwareRecords($row,'display',$importer);
   }
   $row=$wizard['analysis']??$row;$records=splitHardwareRecords($row,$step,$importer);
   foreach($records as &$r){$r['_source_file']=$wizard['_source_file']??basename($_FILES['csv']['name']);unset($r['_skipped']);}unset($r);$wizard['_source_file']=$wizard['_source_file']??basename($_FILES['csv']['name']);
   unset($r);$wizard['records'][$step]=$records;
   
   $_SESSION['_hardware_wizard']=$wizard;redirect('asset_wizard.php?step='.$step);
  }catch(Throwable $e){$error=$e->getMessage();}
 }
 if($step!=='review'&&$action==='skip'){
  $wizard['records'][$step]=[];$_SESSION['_hardware_wizard']=$wizard;$i=array_search($step,$visibleSteps,true);redirect($i!==false&&$i<count($visibleSteps)-1?'asset_wizard.php?step='.$visibleSteps[$i+1]:'asset_wizard.php?step=review');
 }
 if($step!=='review'&&$action==='next'){
  $records=stageRecords($wizard,$step);$posted=(array)($_POST['records']??[]);
  if(!$records)$records=[[]];$out=[];
  foreach($records as $idx=>$record){
   foreach((array)($posted[$idx]['technical']??[]) as $k=>$v){$s=trim((string)$v);if(in_array($k,$arrayFields,true)){$d=json_decode($s,true);$record[$k]=(json_last_error()===JSON_ERROR_NONE&&is_array($d))?array_values($d):($s===''?[]:$importer->split($s));}else$record[$k]=$s;}
   $record['asset_no']=trim((string)($posted[$idx]['asset_no']??''));$record['location']=trim((string)($posted[$idx]['location']??''));$record['personnel_id']=trim((string)($posted[$idx]['personnel_id']??''));
   if(!preg_match('/^\d{7}$/',$record['asset_no']))throw new RuntimeException('شماره اموال در ردیف '.($idx+1).' باید دقیقاً ۷ رقم باشد.');
   if($record['location']==='')throw new RuntimeException('مکان در ردیف '.($idx+1).' الزامی است.');
   $q=db()->prepare('SELECT asset_no FROM assets WHERE asset_no=?');$q->execute([$record['asset_no']]);if($q->fetchColumn())throw new RuntimeException('شماره اموال '.$record['asset_no'].' قبلاً ثبت شده است.');
   $out[]=$record;
  }
  $wizard['records'][$step]=$out;$_SESSION['_hardware_wizard']=$wizard;$i=array_search($step,$steps,true);redirect($i<count($steps)-1?'asset_wizard.php?step='.$steps[$i+1]:'asset_wizard.php?step=review');
 }
 if($step==='review'&&$action==='finish'){
  try{
   $all=[];foreach($steps as $type){$rs=stageRecords($wizard,$type);foreach($rs as $r){if(empty($r['asset_no']))throw new RuntimeException('مرحله '.$types[$type].' کامل نشده است یا باید رد شود.');$all[]=[$type,$r];}}
   if(!$all)throw new RuntimeException('حداقل یک تجهیز باید ثبت شود.');$seen=[];foreach($all as [$type,$r]){if(isset($seen[$r['asset_no']]))throw new RuntimeException('شماره اموال '.$r['asset_no'].' تکراری است.');$seen[$r['asset_no']]=1;}
   $db=db();$db->beginTransaction();
   foreach($all as [$type,$r]){
    $technical=[];foreach($r as $k=>$v){if($k===''||$k[0]==='_'||isset($usageMap[$k])||in_array($k,['asset_no','location','personnel_id'],true))continue;if($type==='computer'&&in_array($k,['IPAddresses','MACAddresses','SubnetMasks','Gateways','DNSServers','MonitorDetails'],true))continue;if(isset($stageFields[$type])&&!in_array($k,$stageFields[$type],true))continue;$technical[$k]=$v;}
    if($type==='display'&&isset($r['MonitorDetails']))$technical['MonitorDetails']=$r['MonitorDetails'];
    $hostname=$r['ComputerName']??$r['PrinterName']??$r['ScannerName']??$r['DisplayName']??null;$manufacturer=$r['PrinterManufacturer']??$r['Manufacturer']??$r['ScannerManufacturer']??$r['DisplayManufacturer']??'';$model=$r['PrinterModel']??$r['Model']??$r['ScannerModel']??$r['DisplayModel']??'';$serial=$r['PrinterSerial']??$r['SystemSerial']??$r['ScannerSerial']??$r['DisplaySerial']??'';
    $q=$db->prepare('SELECT asset_no FROM assets WHERE asset_no=?');$q->execute([$r['asset_no']]);if($q->fetchColumn())throw new RuntimeException('شماره اموال '.$r['asset_no'].' قبلاً ثبت شده است.');
    $q=$db->prepare('INSERT INTO assets(asset_no,asset_type,device_subtype,hostname,personnel_id,location,manufacturer,model,serial_no,technical,status,source_file,created_by,updated_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)');$q->execute([$r['asset_no'],$type,$r['device_subtype']??null,$hostname,($r['personnel_id']??'')!==''?(int)$r['personnel_id']:null,$r['location'],$manufacturer,$model,$serial,json_encode($technical,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE),'active',$r['_source_file']??null,user()['id'],user()['id']]);
    if($type==='computer'){
     $u=[];foreach($usageMap as $src=>$col)$u[$col]=($r[$src]??'')===''?null:$r[$src];$u['last_boot_time']=parseDateTimeValue($u['last_boot_time']);$u['last_shutdown_time']=parseDateTimeValue($u['last_shutdown_time']);$u['collected_at']=parseDateTimeValue($u['collected_at']);
     $q=$db->prepare('INSERT INTO usage_stats(asset_no,boot_count,normal_shutdown_count,unexpected_shutdown_count,user_shutdown_count,last_boot_time,last_shutdown_time,completed_session_count,current_session_hours,current_session_duration,total_usage_hours,total_usage_duration,average_session_hours,average_session_duration,longest_session_hours,longest_session_duration,collected_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE boot_count=VALUES(boot_count),normal_shutdown_count=VALUES(normal_shutdown_count),unexpected_shutdown_count=VALUES(unexpected_shutdown_count),user_shutdown_count=VALUES(user_shutdown_count),last_boot_time=VALUES(last_boot_time),last_shutdown_time=VALUES(last_shutdown_time),completed_session_count=VALUES(completed_session_count),current_session_hours=VALUES(current_session_hours),current_session_duration=VALUES(current_session_duration),total_usage_hours=VALUES(total_usage_hours),total_usage_duration=VALUES(total_usage_duration),average_session_hours=VALUES(average_session_hours),average_session_duration=VALUES(average_session_duration),longest_session_hours=VALUES(longest_session_hours),longest_session_duration=VALUES(longest_session_duration),collected_at=VALUES(collected_at)');
     $q->execute([$r['asset_no'],$u['boot_count'],$u['normal_shutdown_count'],$u['unexpected_shutdown_count'],$u['user_shutdown_count'],$u['last_boot_time'],$u['last_shutdown_time'],$u['completed_session_count'],$u['current_session_hours'],$u['current_session_duration'],$u['total_usage_hours'],$u['total_usage_duration'],$u['average_session_hours'],$u['average_session_duration'],$u['longest_session_hours'],$u['longest_session_duration'],$u['collected_at']]);
     foreach($importer->disks($r) as $d){$q=$db->prepare('INSERT INTO asset_disks(asset_no,disk_index,model,size_gb,serial_no,interface_name,media) VALUES(?,?,?,?,?,?,?)');$q->execute([$r['asset_no'],$d['index'],$d['model'],$d['size_gb'],$d['serial_no'],$d['interface']??null,$d['media']??null]);}
     foreach($importer->ramSlots($r) as $x){$q=$db->prepare('INSERT INTO asset_ram_slots(asset_no,slot_no,state,capacity_gb,ram_type,speed_mhz,manufacturer,part_number,serial_no) VALUES(?,?,?,?,?,?,?,?,?)');$q->execute([$r['asset_no'],$x['slot_no'],$x['state']??'unknown',$x['capacity_gb']??null,$x['ram_type']??null,$x['speed_mhz']??null,$x['manufacturer']??null,$x['part_number']??null,$x['serial_no']??null]);}
    }
    audit('hardware_wizard_asset_created','asset',$r['asset_no'],['type'=>$type,'source'=>$r['_source_file']??null,'unit_index'=>$r['_unit_index']??1]);
   }
   $db->commit();unset($_SESSION['_hardware_wizard']);flash('success','تجهیزات با موفقیت ثبت شدند.');redirect('index.php');
  }catch(Throwable $e){if(isset($db)&&$db->inTransaction())$db->rollBack();$error=$e->getMessage();}
 }
}
$wizard=$_SESSION['_hardware_wizard']??$wizard;
$people=db()->query('SELECT id,first_name,last_name,full_name,personnel_code,national_id FROM personnel ORDER BY last_name,first_name,full_name')->fetchAll();$records=stageRecords($wizard,$step);
?><!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ثبت مرحله‌ای تجهیزات</title><link rel="stylesheet" href="assets/app.css"></head><body><header><a class="brand" href="index.php">HWiNFO · پایش سخت‌افزار</a><nav><a href="index.php">داشبورد</a><a href="assets.php">تجهیزات</a><a href="report.php">گزارش‌ها</a><a href="logout.php">خروج</a></nav></header><main><div class="page-head"><div><div class="eyebrow">ثبت مرحله‌ای</div><h1><?=$step==='review'?'تأیید نهایی':'تحلیل '.$types[$step]?></h1><p class="muted">هر تجهیز شماره اموال و پرسنل مستقل دارد.</p></div></div><div class="wizard-steps"><?php foreach($steps as $x):$available=!empty(stageRecords($wizard,$x))||$x==='computer'||$x==='display'&&isset($wizard['analysis']); if($x!=='computer'&&!$available)continue;?><a class="wizard-step <?=$step===$x?'active':''?>" href="asset_wizard.php?step=<?=$x?>"><b><?=array_search($x,$steps,true)+1?></b><span><?=$types[$x]?></span></a><?php endforeach;?><a class="wizard-step <?=$step==='review'?'active':''?>" href="asset_wizard.php?step=review"><b>✓</b><span>تأیید</span></a></div><?php if($error):?><div class="alert danger"><?= $error ?></div><?php endif;?>
<?php if($step==='review'):?><section class="review-grid"><?php foreach($steps as $type):$rs=stageRecords($wizard,$type);?><article class="panel review-card"><div class="section-title"><h2><?=e($types[$type])?></h2><a class="btn small" href="asset_wizard.php?step=<?=$type?>">ویرایش</a></div><?php if(!$rs):?><p class="muted">ثبت نمی‌شود.</p><?php else:foreach($rs as $r):?><div class="review-fields"><div><span>شماره اموال</span><strong><?=e($r['asset_no']??'—')?></strong></div><div><span>پرسنل</span><strong><?=e($r['personnel_id']??'—')?></strong></div><div><span>مدل</span><strong><?=e($r['Model']??$r['PrinterModel']??$r['ScannerModel']??$r['DisplayModel']??'—')?></strong></div></div><?php endforeach;endif;?></article><?php endforeach;?></section><form method="post"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="step" value="review"><div class="actions sticky-confirm"><button class="btn primary" name="action" value="finish">✓ تأیید و ثبت</button><button class="btn" name="action" value="reset">شروع مجدد</button></div></form>
<?php else:?><section class="panel"><h2>۱. تحلیل CSV</h2><form method="post" enctype="multipart/form-data" class="upload-row"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="step" value="<?=e($step)?>"><input type="file" name="csv" accept=".csv,text/csv" required><button class="btn" name="action" value="analyze">تحلیل CSV</button><button class="btn" name="action" value="skip">رد کردن این مرحله</button></form></section>
<form method="post"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="step" value="<?=e($step)?>"><?php if($records):?><section class="panel"><h2>۲. تجهیزات شناسایی‌شده: <?=count($records)?></h2><?php foreach($records as $i=>$r):?><article class="panel"><h3><?=e($types[$step])?> شماره <?=($i+1)?></h3><div class="form-grid"><label>شماره اموال *<input name="records[<?=$i?>][asset_no]" value="<?=e($r['asset_no']??'')?>" pattern="\d{7}" maxlength="7" required></label><label>پرسنل *<input class="personnel-search" list="personnel-list-<?=$i?>" data-target="personnel-id-<?=$i?>" value="<?=e($r['_personnel_label']??'')?>" placeholder="بخشی از نام یا نام خانوادگی را بنویسید" autocomplete="off" required><input type="hidden" id="personnel-id-<?=$i?>" name="records[<?=$i?>][personnel_id]" value="<?=e($r['personnel_id']??'')?>"><datalist id="personnel-list-<?=$i?>"><?php foreach($people as $p):?><option value="<?=e($p['full_name'].' — '.($p['national_id']??$p['personnel_code']??''))?>" data-id="<?=$p['id']?>"></option><?php endforeach;?></datalist></label><label>مکان *<input name="records[<?=$i?>][location]" value="<?=e($r['location']??'')?>" required></label><label>برند<input value="<?=e($r['Manufacturer']??'')?>" disabled></label><label>مدل<input value="<?=e($r['Model']??$r['PrinterModel']??$r['ScannerModel']??$r['DisplayModel']??'')?>" disabled></label></div><h4>اطلاعات تحلیل‌شده</h4><div class="form-grid"><?php $keys=$step==='computer'?array_keys($r):($stageFields[$step]??[]);if($step==='display')$keys=['MonitorDetails','DisplayTechnology','DisplaySize','DisplayResolution','DisplaySerial','Connection'];foreach($keys as $k):if(!array_key_exists($k,$r))continue;?><label><?=e(wl($k))?><textarea name="records[<?=$i?>][technical][<?=e($k)?>]" rows="3" <?=in_array($k,$arrayFields,true)?'dir="ltr"':''?>><?=e(wv($r[$k]))?></textarea></label><?php endforeach;?></div></article><?php endforeach;?><div class="actions sticky-confirm"><button class="btn primary" name="action" value="next">ذخیره مرحله و ادامه</button></div></section><?php endif;?></form><?php endif;?></main></body></html>