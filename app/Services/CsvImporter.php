<?php
declare(strict_types=1);

final class CsvImporter {
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
            yield array_combine($headers, array_slice($row, 0, count($headers)));
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

    public function disks(array $r): array {
        $explicit = ['DiskModels','DiskSizesGB','DiskSerials','DiskInterfaces'];
        if (array_filter($explicit, fn($k) => isset($r[$k]) && trim((string)$r[$k]) !== '')) {
            $models=$this->split((string)($r['DiskModels']??'')); $sizes=$this->split((string)($r['DiskSizesGB']??''));
            $serials=$this->split((string)($r['DiskSerials']??'')); $interfaces=$this->split((string)($r['DiskInterfaces']??''));
            $n=max(count($models),count($sizes),count($serials),count($interfaces)); $out=[];
            for($i=0;$i<$n;$i++) $out[]=['index'=>$i+1,'model'=>$models[$i]??null,'size_gb'=>$this->number($sizes[$i]??null),'serial_no'=>$serials[$i]??null,'interface'=>$interfaces[$i]??null];
            return $out;
        }
        return $this->parseDiskDetails((string)($r['DiskDetails'] ?? ''));
    }

    private function parseDiskDetails(string $value): array {
        $value=trim($value); if($value==='') return [];
        $records=preg_split('/\s*,\s*(?=[A-Za-z0-9][^,]*?\/\s*[0-9]+(?:\.[0-9]+)?\s*GB)/u',$value) ?: [$value];
        $out=[];
        foreach($records as $i=>$text) {
            $item=['index'=>$i+1,'model'=>null,'size_gb'=>null,'serial_no'=>null,'interface'=>null,'media'=>null,'raw'=>trim($text)];
            if(preg_match('/^\s*(.*?)\s*\/\s*([0-9]+(?:\.[0-9]+)?)\s*GB\s*\/\s*Serial\s*:\s*([^\/]+?)\s*\/\s*Interface\s*:\s*([^,\/]+)(?:\s*\/\s*Media\s*:\s*(.*))?$/iu',trim($text),$m)) {
                $item['model']=trim($m[1]); $item['size_gb']=$this->number($m[2]); $item['serial_no']=trim($m[3]); $item['interface']=trim($m[4]); $item['media']=isset($m[5])?trim($m[5]):null;
            } else {
                if(preg_match('/^\s*([^\/]+)\s*\/\s*([0-9]+(?:\.[0-9]+)?)\s*GB/i',trim($text),$m)){ $item['model']=trim($m[1]); $item['size_gb']=$this->number($m[2]); }
                if(preg_match('/Serial\s*:\s*([^\/]+)/i',$text,$m)) $item['serial_no']=trim($m[1]);
                if(preg_match('/Interface\s*:\s*([^,\/]+)/i',$text,$m)) $item['interface']=trim($m[1]);
                if(preg_match('/Media\s*:\s*(.+)$/i',$text,$m)) $item['media']=trim($m[1]);
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

    private function number(?string $value): ?float { if($value===null || trim($value)==='') return null; return is_numeric(str_replace(',','',trim($value))) ? (float)str_replace(',','',trim($value)) : null; }
}
