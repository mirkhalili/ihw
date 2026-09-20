<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require_role(['admin']);

$error=null;$result=null;

function personnel_header(string $v):string {
    $v=trim(preg_replace('/^\\xEF\\xBB\\xBF/','',$v));
    $v=str_replace(['‌',' '],['',''],mb_strtolower($v,'UTF-8'));
    return match($v){
        'نام','firstname','givenname','first_name'=>'first_name',
        'نامخانوادگی','نامفامیلی','lastname','surname','last_name'=>'last_name',
        'کدملی','کدملی','nationalid','national_id','کدملیفرد'=>'national_id',
        'تلفنهمراه','شمارههمراه','موبایل','mobile','phone','phonenumber'=>'mobile',
        default=>$v
    };
}
function read_personnel_csv(string $path):array {
    $fh=fopen($path,'rb'); if(!$fh) throw new RuntimeException('خواندن فایل ممکن نیست.');
    $first=fgets($fh); if($first===false) throw new RuntimeException('فایل خالی است.');
    $first=preg_replace('/^\\xEF\\xBB\\xBF/','',$first);
    $sample=$first;
    $delim=substr_count($sample,';')>substr_count($sample,",")?';':(substr_count($sample,"\t")>substr_count($sample,",")?"\t":",");
    rewind($fh);
    $headers=fgetcsv($fh,0,$delim);
    if(!$headers) throw new RuntimeException('سطر عنوان ستون‌ها یافت نشد.');
    $map=[]; foreach($headers as $i=>$h)$map[personnel_header((string)$h)]=$i;
    foreach(['first_name','last_name','national_id','mobile'] as $required) if(!array_key_exists($required,$map)) throw new RuntimeException('ستون الزامی «'.$required.'» در فایل یافت نشد.');
    $rows=[];$line=1;
    while(($row=fgetcsv($fh,0,$delim))!==false){$line++;if(count(array_filter($row,fn($x)=>trim((string)$x)!==''))===0)continue;
        $firstName=trim((string)($row[$map['first_name']]??''));$lastName=trim((string)($row[$map['last_name']]??''));$national=preg_replace('/\\D+/','',(string)($row[$map['national_id']]??''));$mobile=trim((string)($row[$map['mobile']]??''));
        if($national===''||$firstName===''||$lastName==='')throw new RuntimeException('سطر '.$line.' باید نام، نام خانوادگی و کدملی داشته باشد.');
        if(!preg_match('/^\\d{10}$/',$national))throw new RuntimeException('کدملی در سطر '.$line.' باید ۱۰ رقم باشد.');
        $rows[]=['first_name'=>$firstName,'last_name'=>$lastName,'national_id'=>$national,'mobile'=>$mobile];
    }
    fclose($fh);return $rows;
}

if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();try{
    if(empty($_FILES['personnel_file'])||($_FILES['personnel_file']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) throw new RuntimeException('فایل CSV را انتخاب کنید.');
    $name=(string)$_FILES['personnel_file']['name'];$ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));if($ext!=='csv')throw new RuntimeException('فقط فایل CSV پذیرفته می‌شود.');
    $rows=read_personnel_csv((string)$_FILES['personnel_file']['tmp_name']);if(!$rows)throw new RuntimeException('هیچ رکورد معتبری در فایل وجود ندارد.');
    $db=db();$db->beginTransaction();$inserted=0;$updated=0;
    $find=$db->prepare('SELECT id FROM personnel WHERE national_id=? LIMIT 1');
    $update=$db->prepare('UPDATE personnel SET first_name=?,last_name=?,full_name=?,mobile=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
    $insert=$db->prepare('INSERT INTO personnel(first_name,last_name,national_id,full_name,mobile) VALUES(?,?,?,?,?)');
    foreach($rows as $r){$find->execute([$r['national_id']]);$id=$find->fetchColumn();$full=trim($r['first_name'].' '.$r['last_name']);if($id){$update->execute([$r['first_name'],$r['last_name'],$full,$r['mobile'],$id]);$updated++;}else{$insert->execute([$r['first_name'],$r['last_name'],$r['national_id'],$full,$r['mobile']]);$inserted++;}}
    $db->commit();audit('personnel_import','personnel',null,['filename'=>$name,'total'=>count($rows),'inserted'=>$inserted,'updated'=>$updated]);$result=['total'=>count($rows),'inserted'=>$inserted,'updated'=>$updated];
}catch(Throwable $e){if(isset($db)&&$db->inTransaction())$db->rollBack();$error=$e->getMessage();}}

?><!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ورود اطلاعات پرسنل</title><link rel="stylesheet" href="assets/app.css"></head><body><header><a class="brand" href="index.php">سامانه سخت‌افزار</a><nav><a href="personnel.php">پرسنل</a><a href="assets.php">تجهیزات</a><a href="logout.php">خروج</a></nav></header><main><div class="page-head"><div><h1>ورود اطلاعات پرسنل از فایل</h1><p class="muted">ستون‌ها: نام، نام خانوادگی، کدملی، تلفن همراه. کدملی کلید تطبیق است.</p></div></div><?php if($error):?><div class="alert danger"><?=e($error)?></div><?php endif;?><?php if($result):?><div class="alert success">تعداد <?=$result['total']?> رکورد پردازش شد؛ <?=$result['inserted']?> نفر جدید و <?=$result['updated']?> نفر به‌روزرسانی شدند.</div><?php endif;?><section class="panel"><form method="post" enctype="multipart/form-data"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><label>فایل CSV<input type="file" name="personnel_file" accept=".csv,text/csv" required></label><div class="actions"><button class="btn primary">شروع ورود اطلاعات</button><a class="btn" href="personnel.php">بازگشت</a></div></form></section></main></body></html>
