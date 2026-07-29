<?php

declare(strict_types=1);

namespace Koravik\Districts\Beacon;

use Koravik\Platform\Database\Database;

final class BeaconDomainService
{
    public function __construct(private readonly Database $database) {}

    public function resolveHost(string $host): ?array
    {
        $host=strtolower(trim(preg_replace('/:\d+$/','',$host)??''));
        if($host==='')return null;
        $s=$this->database->pdo()->prepare('SELECT * FROM beacon_domains WHERE hostname=:host AND verification_status="verified" LIMIT 1');
        $s->execute(['host'=>$host]);
        return $s->fetch()?:null;
    }

    public function resolveLink(string $host,string $slug): ?array
    {
        $domain=$this->resolveHost($host);if(!$domain)return null;
        $s=$this->database->pdo()->prepare('SELECT l.* FROM beacon_short_links l WHERE l.domain_id=:domain AND l.slug=:slug AND l.status="active" LIMIT 1');
        $s->execute(['domain'=>$domain['id'],'slug'=>$slug]);$row=$s->fetch();if(!$row)return null;
        $this->database->pdo()->prepare('UPDATE beacon_short_links SET visit_count=visit_count+1,updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['id'=>$row['id']]);
        return $row;
    }

    public function defaultDomainId(): string
    {
        $id=$this->database->pdo()->query('SELECT id FROM beacon_domains WHERE is_default=1 AND verification_status="verified" ORDER BY domain_type="platform" DESC LIMIT 1')->fetchColumn();
        return $id?(string)$id:'00000000-0000-4000-8000-000000000001';
    }

    public function publicUrl(string $slug,?string $domainId=null): string
    {
        $id=$domainId?:$this->defaultDomainId();$s=$this->database->pdo()->prepare('SELECT hostname FROM beacon_domains WHERE id=:id AND verification_status="verified"');$s->execute(['id'=>$id]);$host=(string)($s->fetchColumn()?:'krvk.nl');
        return 'https://'.$host.'/'.rawurlencode($slug);
    }
}