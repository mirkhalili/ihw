<?php
declare(strict_types=1);

final class CsvImporter {
    /** Fields whose pipe-delimited values are stored as JSON arrays. */
    private const ARRAY_FIELDS = [
        'IPAddresses','MACAddresses','SubnetMasks','Gateways','DNSServers',
        'RAMManufacturers','RAMPartNumbers','RAMSerialNumbers','RAMSpeedsMHz',
        'DiskModels','DiskSizesGB','DiskSerials','DiskInterfaces',
        'PrinterNames','PrinterPorts','PrinterDrivers','DuplexPrinters','PrinterIPAddresses','PrinterMACAddresses','ScannerNames','ScannerManufacturers',
    ];

    public function rows(string $path): Generator {
        $h = fopen($path, 'rb');
        if (!$h) throw new RuntimeException('CSV قابل خواندن نیست.');
        $first = fgets($h);
        if ($first === false) { fclose($h); throw new RuntimeException('CSV خالی است.'); }
        $first = preg_replace('/^\xEF\xBB\xBF/u', '', $first);
        $delimiter = $this->detectDelimiter($first);
        rewind($h);
        $headers = fgetcsv($h, 0, $delimiter);
        if (!$headers) { fclose($h); throw new RuntimeException('هدر CSV یافت نشد.'); }
        $headers = array_map(fn($x) => trim((string)$x), $headers);
        while (($row = fgetcsv($h, 0, $delimiter)) !== false) {
            if (count($row) === 1 && trim((string)$row[0]) === '') continue;
            $row = array_pad($row, count($headers), '');
            $record = array_combine($headers, array_slice($row, 0, count($headers)));
            yield $this->normalizeRow($record ?: []);
        }
        fclose($h);
    }

    private function detectDelimiter(string $line): string {
        $scores = [',' => substr_count($line, ','), ';' => substr_count($line, ';'), "\t" => substr_count($line, "\t")];
        arsort($scores);
        return (string)array_key_first($scores);
    }

    public function split(string $value): array {
        $value = trim($value);
        if ($value === '') return [];
        return array_values(array_filter(array_map('trim', preg_split('/\s*\|\s*/u', $value) ?: []), fn($v) => $v !== ''));
    }

    /** Convert pipe-delimited CSV fields to arrays and normalize printer/scanner details. */
    public function normalizeRow(array $row): array {
        foreach (self::ARRAY_FIELDS as $key) {
            if (!array_key_exists($key, $row)) continue;
            $value = $row[$key];
            if (is_array($value)) continue;
            $value = trim((string)$value);
            if (in_array($key, ['PrinterNames','PrinterPorts','PrinterDrivers','DuplexPrinters','ScannerNames','ScannerManufacturers'], true)) {
                $row[$key] = $this->commaList($value);
            } else {
                $row[$key] = str_contains($value, '|') ? $this->split($value) : ($value === '' ? [] : [$value]);
            }
        }
        foreach ([
            'PrinterName' => 'PrinterNames',
            'PrinterPort' => 'PrinterPorts',
            'PrinterDriver' => 'PrinterDrivers',
            'DuplexPrinter' => 'DuplexPrinters',
            'PrinterIPAddress' => 'PrinterIPAddresses',
            'PrinterMACAddress' => 'PrinterMACAddresses',
        ] as $singular => $plural) {
            if (!array_key_exists($plural, $row) && array_key_exists($singular, $row)) {
                $row[$plural] = $row[$singular];
            }
        }
        $row = $this->normalizePrinterData($row);
        $row = $this->normalizeNetworkData($row);
        $row = $this->normalizeScannerData($row);
        return $row;
    }

    private function commaList(string $value): array {
        if ($value === '') return [];
        return array_values(array_filter(array_map('trim', preg_split('/\s*,\s*/u', $value) ?: []), fn($v) => $v !== ''));
    }

    private function normalizePrinterData(array $row): array {
        $detailsValue = $row['PrinterDetails'] ?? '';
        $details = is_array($detailsValue) ? implode(' || ', array_map('strval', $detailsValue)) : trim((string)$detailsValue);
        $records = $details === '' ? [] : array_values(array_filter(
            array_map('trim', preg_split('/\s*\|\|\s*/u', $details) ?: []),
            fn($v) => $v !== ''
        ));

        $names = $this->listValue($row['PrinterNames'] ?? []);
        $ports = $this->listValue($row['PrinterPorts'] ?? []);
        $drivers = $this->listValue($row['PrinterDrivers'] ?? []);
        $duplex = $this->listValue($row['DuplexPrinters'] ?? []);

        $kept = [];
        foreach ($records as $record) {
            $parsed = $this->parsePrinterRecord($record);
            $name = $parsed['name'];
            if ($name !== '' && $this->isWindowsVirtualPrinter($name)) continue;
            $kept[] = $record;
        }

        foreach ($kept as $i => $record) {
            $parsed = $this->parsePrinterRecord($record);
            if ($parsed['name'] !== '' && !isset($names[$i])) $names[$i] = $parsed['name'];
            if ($parsed['driver'] !== '' && !isset($drivers[$i])) $drivers[$i] = $parsed['driver'];
            if ($parsed['port'] !== '' && !isset($ports[$i])) $ports[$i] = $parsed['port'];
            if ($parsed['duplex'] !== '' && !isset($duplex[$i])) $duplex[$i] = $parsed['duplex'];
        }

        $row['PrinterDetails'] = implode(' || ', $kept);
        $row['PrinterNames'] = $this->filterPrinterList($names);
        $row['PrinterPorts'] = array_values($ports);
        $row['PrinterDrivers'] = array_values($drivers);
        $row['DuplexPrinters'] = array_values($duplex);

        $row['PrinterCount'] = max(
            count($kept), count($row['PrinterNames']), count($row['PrinterPorts']),
            count($row['PrinterDrivers']), count($row['DuplexPrinters'])
        );

        if (isset($row['DefaultPrinterName'])) {
            $default = is_array($row['DefaultPrinterName'])
                ? (string)($row['DefaultPrinterName'][0] ?? '')
                : trim((string)$row['DefaultPrinterName']);
            $row['DefaultPrinterName'] = $this->isWindowsVirtualPrinter($default) ? '' : $default;
        }
        return $row;
    }

    private function listValue(mixed $value): array {
        if (is_array($value)) {
            return array_values(array_filter(array_map(fn($v) => trim((string)$v), $value), fn($v) => $v !== ''));
        }
        return $this->commaList(trim((string)$value));
    }

    private function filterPrinterList(array $values): array {
        return array_values(array_filter($values, fn($v) => !$this->isWindowsVirtualPrinter((string)$v)));
    }

    private function isWindowsVirtualPrinter(string $value): bool {
        return (bool)preg_match('/\b(Adobe|Microsoft Print to PDF|Microsoft XPS Document Writer|Fax|OneNote|Send To OneNote|Print to File)\b/i', $value);
    }

    private function parsePrinterRecord(string $record): array {
        $parts = array_values(array_filter(array_map('trim', preg_split('/\s*\/\s*/u', $record) ?: []), fn($v) => $v !== ''));
        $out = ['name' => '', 'driver' => '', 'port' => '', 'duplex' => ''];

        foreach ($parts as $part) {
            if (preg_match('/^(?:name|printer|نام(?: چاپگر)?)\s*:\s*(.+)$/iu', $part, $m)) $out['name'] = trim($m[1]);
            elseif (preg_match('/^(?:driver|درایور)\s*:\s*(.+)$/iu', $part, $m)) $out['driver'] = trim($m[1]);
            elseif (preg_match('/^(?:port|پورت)\s*:\s*(.+)$/iu', $part, $m)) $out['port'] = trim($m[1]);
            elseif (preg_match('/^(?:duplex|دورو|چاپ دورو)\s*:\s*(.+)$/iu', $part, $m)) $out['duplex'] = trim($m[1]);
        }

        if ($out['name'] === '' && isset($parts[0])) $out['name'] = $parts[0];
        if ($out['driver'] === '' && isset($parts[1]) && !preg_match('/^(?:port|پورت|duplex|دورو)/iu', $parts[1])) $out['driver'] = $parts[1];
        if ($out['port'] === '' && isset($parts[2]) && !preg_match('/^(?:duplex|دورو)/iu', $parts[2])) $out['port'] = $parts[2];
        if ($out['duplex'] === '' && isset($parts[3])) $out['duplex'] = $parts[3];

        return $out;
    }

    private function normalizeScannerData(array $row): array {
        if (!array_key_exists('ScannerDetails', $row)) return $row;
        $details = trim((string)$row['ScannerDetails']);
        if ($details === '') return $row;
        $records = array_values(array_filter(array_map('trim', preg_split('/\s*\|\|\s*/u', $details) ?: []), fn($v) => $v !== ''));
        $row['ScannerDetails'] = implode(' || ', $records);
        if (!isset($row['ScannerCount']) || trim((string)$row['ScannerCount']) === '') $row['ScannerCount'] = count($records);
        return $row;
    }


    private function normalizeNetworkData(array $row): array {
        foreach (['NetworkDetails','IPAddresses','MACAddresses','SubnetMasks','Gateways','DNSServers'] as $key) {
            if (!array_key_exists($key,$row)) continue;
            $values=is_array($row[$key])?$row[$key]:$this->split((string)$row[$key]);
            $values=array_values(array_filter(array_map('trim',$values),fn($v)=>$v!==''));
            $row[$key]=$values;
        }
        if(isset($row['NetworkDetails']) && is_array($row['NetworkDetails'])){
            $row['NetworkDetails']=array_values(array_filter($row['NetworkDetails'],fn($v)=>!$this->isWindowsVirtualAdapter((string)$v)));
        }
        foreach (['IPAddresses','MACAddresses','SubnetMasks','Gateways','DNSServers'] as $key) {
            if(isset($row[$key]) && is_array($row[$key])) $row[$key]=array_values(array_filter($row[$key],fn($v)=>!$this->isWindowsVirtualValue((string)$v)));
        }
        return $row;
    }

    private function isWindowsVirtualAdapter(string $value): bool {
        return (bool)preg_match('/Microsoft (Wi-?Fi Direct|Kernel Debug|KM-TEST|Hyper-V|Loopback)|Hyper-V Virtual Ethernet|Teredo|ISATAP|6to4|Npcap Loopback|WAN Miniport|VirtualBox|VMware Virtual|Default Switch/i',$value);
    }

    private function isWindowsVirtualValue(string $value): bool {
        $v=trim($value);
        return $this->isWindowsVirtualAdapter($v) || (bool)preg_match('/^(127\\.|169\\.254\\.|0\\.0\\.0\\.0$|::1$|fe80::)/i',$v);
    }

    public static function arrayFields(): array { return self::ARRAY_FIELDS; }

    public function disks(array $r): array {
        $explicit = ['DiskModels','DiskSizesGB','DiskSerials','DiskInterfaces'];
        if (array_filter($explicit, fn($k) => isset($r[$k]) && ((is_array($r[$k]) && $r[$k]) || trim((string)$r[$k]) !== ''))) {
            $models=$this->asList($r['DiskModels']??[]); $sizes=$this->asList($r['DiskSizesGB']??[]);
            $serials=$this->asList($r['DiskSerials']??[]); $interfaces=$this->asList($r['DiskInterfaces']??[]);
            $n=max(count($models),count($sizes),count($serials),count($interfaces)); $out=[];
            for($i=0;$i<$n;$i++) $out[]=['index'=>$i+1,'model'=>$models[$i]??null,'size_gb'=>$this->number($sizes[$i]??null),'serial_no'=>$serials[$i]??null,'interface'=>$interfaces[$i]??null,'media'=>null];
            return $out;
        }
        return $this->parseDiskDetails((string)($r['DiskDetails'] ?? ''));
    }

    private function asList(mixed $value): array {
        if (is_array($value)) return array_values(array_filter(array_map('trim', $value), fn($v) => $v !== ''));
        return $this->split((string)$value);
    }

    private function parseDiskDetails(string $value): array {
        $value=trim($value); if($value==='') return [];
        $records=preg_split('/\s*,\s*(?=[^,\/]+\s*\/\s*[0-9]+(?:\.[0-9]+)?\s*GB\b)/iu',$value) ?: [$value];
        $out=[];
        foreach($records as $i=>$text) {
            $text=trim($text);
            $item=['index'=>$i+1,'model'=>null,'size_gb'=>null,'serial_no'=>null,'interface'=>null,'media'=>null,'raw'=>$text];
            if(preg_match('/^\s*(.*?)\s*\/\s*([0-9]+(?:\.[0-9]+)?)\s*GB\s*(?:\/\s*Serial\s*:\s*([^\/]+?))?(?:\s*\/\s*Interface\s*:\s*([^,\/]+))?(?:\s*\/\s*Media\s*:\s*([^,]+))?\s*$/iu',$text,$m)) {
                $item['model']=trim($m[1]); $item['size_gb']=$this->number($m[2]);
                $item['serial_no']=isset($m[3])&&trim($m[3])!==''?trim($m[3]):null;
                $item['interface']=isset($m[4])&&trim($m[4])!==''?trim($m[4]):null;
                $item['media']=isset($m[5])&&trim($m[5])!==''?trim($m[5]):null;
            } else {
                if(preg_match('/^\s*([^\/]+)\s*\/\s*([0-9]+(?:\.[0-9]+)?)\s*GB/i',$text,$m)){ $item['model']=trim($m[1]); $item['size_gb']=$this->number($m[2]); }
                if(preg_match('/Serial\s*:\s*([^\/]+)/i',$text,$m)) $item['serial_no']=trim($m[1]);
                if(preg_match('/Interface\s*:\s*([^,\/]+)/i',$text,$m)) $item['interface']=trim($m[1]);
                if(preg_match('/Media\s*:\s*([^,\/]+)/i',$text,$m)) $item['media']=trim($m[1]);
            }
            $out[]=$item;
        }
        return $out;
    }

    public function ramSlots(array $r): array {
        $value=trim((string)($r['RAMSlotDetails']??'')); if($value==='') return [];
        $details=preg_split('/\s*,\s*(?=[A-Za-z0-9_ -]+\s*:\s*[0-9]+(?:\.[0-9]+)?\s*GB)/u',$value) ?: [$value];
        $out=[];
        foreach($details as $i=>$text){
            $item=['slot_no'=>$i+1,'state'=>'occupied','raw'=>trim($text)];
            if(preg_match('/^\s*([^:]+):/u',$text,$m)) $item['slot_label']=trim($m[1]);
            foreach(['capacity_gb'=>'/([0-9]+(?:\.[0-9]+)?)\s*GB/i','speed_mhz'=>'/Speed\s*:\s*(\d+)\s*MHz/i','ram_type'=>'/Type\s*:\s*([^\/]+)/i','manufacturer'=>'/Manufacturer\s*:\s*([^\/]+)/i','part_number'=>'/PartNumber\s*:\s*([^\/]+)/i','serial_no'=>'/Serial\s*:\s*([^,\/]+)/i'] as $k=>$rx) if(preg_match($rx,$text,$m)) $item[$k]=trim($m[1]);
            $out[]=$item;
        }
        return $out;
    }

    private function number(mixed $value): ?float { if($value===null || trim((string)$value)==='') return null; $v=str_replace(',','',trim((string)$value)); return is_numeric($v) ? (float)$v : null; }
}
