<?php
declare(strict_types=1);

require_once __DIR__.'/../app/Services/CsvImporter.php';

$importer = new CsvImporter();
$tmp = tempnam(sys_get_temp_dir(), 'ihw_csv_');
if ($tmp === false) throw new RuntimeException('Cannot create temporary CSV.');

try {
    $h = fopen($tmp, 'wb');
    if (!$h) throw new RuntimeException('Cannot open temporary CSV.');
    $headers = ['ComputerName','IPAddresses','MACAddresses','RAMSlotDetails','DiskDetails','BootCount','UnexpectedShutdownCount','TotalUsageHours'];
    fputcsv($h, $headers);
    fputcsv($h, [
        'FILE-SERVER',
        '192.168.1.10|192.168.1.11',
        'AA:BB:CC:DD:EE:01|AA:BB:CC:DD:EE:02',
        'ChannelA-DIMM0: 4 GB / Speed:1333 MHz / Type:DDR3 / Manufacturer:1337 / PartNumber:FLFF65F-C8KM9 / Serial:00000000, ChannelA-DIMM1: 8 GB / Speed:1600 MHz / Type:DDR3 / Manufacturer:1319 / PartNumber:CL11-11-11 D3-1600 / Serial:00000000',
        'TS256GSSD370S / 238.47 GB / Serial:D092280592 / Interface:SCSI / Media:Fixed hard disk media, WDC WD5000AAKS-00V1A0 / 465.76 GB / Serial:WD-WMAWF0317835 / Interface:SCSI / Media:Fixed hard disk media',
        '80', '13', '6846.13'
    ]);
    fclose($h);

    $rows = iterator_to_array($importer->rows($tmp));
    if (count($rows) !== 1) throw new RuntimeException('Expected one CSV row.');
    $row = $rows[0];

    foreach (['IPAddresses','MACAddresses'] as $key) {
        if (!is_array($row[$key]) || count($row[$key]) !== 2) throw new RuntimeException($key.' pipe parsing failed.');
    }

    $disks = $importer->disks($row);
    if (count($disks) !== 2 || $disks[0]['model'] !== 'TS256GSSD370S' || (float)$disks[1]['size_gb'] !== 465.76) {
        throw new RuntimeException('DiskDetails parsing failed.');
    }

    $rams = $importer->ramSlots($row);
    if (count($rams) !== 2 || $rams[0]['slot_label'] !== 'ChannelA-DIMM0' || (float)$rams[1]['capacity_gb'] !== 8.0 || (int)$rams[1]['speed_mhz'] !== 1600) {
        throw new RuntimeException('RAMSlotDetails parsing failed.');
    }

    if ((string)$row['UnexpectedShutdownCount'] !== '13' || (string)$row['TotalUsageHours'] !== '6846.13') {
        throw new RuntimeException('Monitoring fields were not preserved.');
    }

    echo "CSV smoke test: OK\n";
} finally {
    @unlink($tmp);
}
