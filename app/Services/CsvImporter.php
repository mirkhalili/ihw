<?php
declare(strict_types=1);

final class CsvImporter {
    /** Fields whose pipe-delimited values are stored as JSON arrays. */
    private const ARRAY_FIELDS = [
        'IPAddresses','MACAddresses','SubnetMasks','Gateways','DNSServers',
        'RAMManufacturers','RAMPartNumbers','RAMSerialNumbers','RAMSpeedsMHz',
        'DiskModels','DiskSizesGB','DiskSerials','DiskInterfaces',
        'PrinterNames','PrinterPorts','PrinterDrivers','DuplexPrinters','ScannerNames','ScannerManufacturers',
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
        $row = $this->normalizePrinterData($row);
        $row = $this->normalizeScannerData($row);
        return $row;
    }

    private function commaList(string $value): array {
        if ($value === '') return [];
        return array_values(array_filter(array_map('trim', preg_split('/\s*,\s*/u', $value) ?: []), fn($v) => $v !== ''));
    }

    private function normalizePrinterData(array $row): array {
        if (!array_key_exists('PrinterDetails', $row)) return $row;
        $details = trim((string)$row['PrinterDetails']);
        if ($details === '') return $row;
        $records = array_values(array_filter(array_map('trim', preg_split('/\s*\|\|\s*/u', $details) ?: []), fn($v) => $v !== ''));
        $kept = [];
        foreach ($records as $record) {
            $name = trim((string)preg_split('/\s*\/\s*/u', $record, 2)[0]);
            if ($name !== '' && preg_match('/\bAdobe\b/i', $name)) continue;
            $kept[] = $record;
        }
        $row['PrinterDetails'] = implode(' || ', $kept);
        if (!isset($row['PrinterCount']) || trim((string)$row['PrinterCount']) === '') $row['PrinterCount'] = count($kept);
        if (isset($row['DefaultPrinterName']) && preg_match('/\bAdobe\b/i', (string)$row['DefaultPrinterName'])) $row['DefaultPrinterName'] = '';
        return $row;
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
