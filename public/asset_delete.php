<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require_login();
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Method Not Allowed');}
verify_csrf();
$assetNo=preg_replace('/[^0-9]/','',(string)($_POST['asset_no']??''));
if(!preg_match('/^\d{7}$/',$assetNo)){http_response_code(400);exit('شماره اموال نامعتبر است.');}
$q=db()->prepare('SELECT asset_type FROM assets WHERE asset_no=?');$q->execute([$assetNo]);$type=$q->fetchColumn();
if(!$type){flash('danger','تجهیز موردنظر یافت نشد.');redirect('assets.php');}
if(!can_manage_asset_type((string)$type,'edit')){http_response_code(403);exit('برای حذف این تجهیز دسترسی ندارید.');}
$db=db();$db->beginTransaction();
try{
 $q=$db->prepare('DELETE FROM assets WHERE asset_no=?');$q->execute([$assetNo]);
 audit('asset_deleted','asset',$assetNo,['type'=>$type]);
 $db->commit();flash('success','تجهیز '.$assetNo.' حذف شد.');redirect('assets.php?type='.urlencode((string)$type));
}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
