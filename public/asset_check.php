<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require_login();
header('Content-Type: application/json; charset=utf-8');
$assetNo = trim((string)($_GET['asset_no'] ?? ''));
if (!preg_match('/^\d{7}$/', $assetNo)) {
    echo json_encode(['valid'=>false,'duplicate'=>false], JSON_UNESCAPED_UNICODE);
    exit;
}
$st = db()->prepare('SELECT asset_no, asset_type, hostname, manufacturer, model FROM assets WHERE asset_no=? LIMIT 1');
$st->execute([$assetNo]);
$row = $st->fetch();
if (!$row) {
    echo json_encode(['valid'=>true,'duplicate'=>false], JSON_UNESCAPED_UNICODE);
    exit;
}
$labels = ['computer'=>'رایانه','printer'=>'چاپگر','scanner'=>'اسکنر','display'=>'نمایشگر'];
echo json_encode([
    'valid'=>true,
    'duplicate'=>true,
    'asset_no'=>$row['asset_no'],
    'type'=>$labels[$row['asset_type']] ?? $row['asset_type'],
    'hostname'=>$row['hostname'] ?? '',
    'manufacturer'=>$row['manufacturer'] ?? '',
    'model'=>$row['model'] ?? '',
    'edit_url'=>'asset_edit.php?asset_no='.rawurlencode($row['asset_no'])
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
