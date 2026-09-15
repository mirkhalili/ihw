<?php
declare(strict_types=1);

final class ActiveDirectory
{
    private array $cfg;

    public function __construct(array $cfg)
    {
        $this->cfg = $cfg;
    }

    public function test(): array
    {
        $link = $this->connect();
        ldap_unbind($link);
        return ['ok' => true, 'message' => 'اتصال به Active Directory با موفقیت برقرار شد.'];
    }

    public function sync(): int
    {
        $link = $this->connect();
        try {
            $baseDn = trim((string)($this->cfg['base_dn'] ?? ''));
            $filter = trim((string)($this->cfg['user_filter'] ?? '(&(objectCategory=person)(objectClass=user))'));
            $attrs = ['sAMAccountName','displayName','givenName','sn','employeeID','department','title','mail','userPrincipalName','distinguishedName'];
            $search = ldap_search($link, $baseDn, $filter, $attrs);
            if ($search === false) throw new RuntimeException('جستجو در Active Directory ناموفق بود.');
            $entries = ldap_get_entries($link, $search);
            $db = db(); $db->beginTransaction(); $count = 0;
            try {
                $find = $db->prepare('SELECT id FROM personnel WHERE personnel_code=? LIMIT 1');
                $update = $db->prepare('UPDATE personnel SET national_id=?,full_name=?,department=?,position=?,extra=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
                $insert = $db->prepare('INSERT INTO personnel(personnel_code,national_id,full_name,department,position,extra) VALUES(?,?,?,?,?,?)');
                for ($i=0; $i<($entries['count'] ?? 0); $i++) {
                    $e = $entries[$i];
                    $code = $this->value($e, 'employeeID') ?: $this->value($e, 'sAMAccountName');
                    $name = $this->value($e, 'displayName') ?: trim($this->value($e, 'givenName').' '.$this->value($e, 'sn'));
                    if ($code === '' || $name === '') continue;
                    $dept = $this->value($e, 'department'); $position = $this->value($e, 'title');
                    $extra = json_encode([
                        'source'=>'active_directory','sAMAccountName'=>$this->value($e,'sAMAccountName'),
                        'mail'=>$this->value($e,'mail'),'userPrincipalName'=>$this->value($e,'userPrincipalName'),
                        'distinguishedName'=>$this->value($e,'distinguishedName')
                    ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                    $find->execute([$code]); $id = $find->fetchColumn();
                    if ($id) $update->execute([null,$name,$dept,$position,$extra,$id]);
                    else $insert->execute([$code,null,$name,$dept,$position,$extra]);
                    $count++;
                }
                $db->commit();
            } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); throw $e; }
            return $count;
        } finally { ldap_unbind($link); }
    }

    private function connect()
    {
        if (!function_exists('ldap_connect')) throw new RuntimeException('افزونه PHP LDAP روی سرور فعال نیست.');
        $host = trim((string)($this->cfg['host'] ?? ''));
        if ($host === '') throw new RuntimeException('آدرس سرور Active Directory تنظیم نشده است.');
        $port = (int)($this->cfg['port'] ?? 389); $useTls = !empty($this->cfg['use_tls']);
        $scheme = $useTls ? 'ldaps' : 'ldap';
        $link = ldap_connect($scheme.'://'.$host.':'.$port);
        if ($link === false) throw new RuntimeException('اتصال به سرور Active Directory برقرار نشد.');
        ldap_set_option($link, LDAP_OPT_PROTOCOL_VERSION, 3); ldap_set_option($link, LDAP_OPT_REFERRALS, 0);
        $user = trim((string)($this->cfg['bind_dn'] ?? '')); $pass = (string)($this->cfg['bind_password'] ?? '');
        if (!@ldap_bind($link, $user, $pass)) throw new RuntimeException('احراز هویت Active Directory ناموفق بود.');
        return $link;
    }

    private function value(array $entry, string $key): string
    {
        if (!isset($entry[$key][0])) return '';
        return trim((string)$entry[$key][0]);
    }
}
