<?php

declare(strict_types=1);

namespace Koravik\Districts\Beacon;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class BeaconService
{
    public function __construct(private readonly Database $database) {}

    public function dashboard(string $accountId): array
    {
        $pdo=$this->database->pdo();
        return [
            'links'=>$this->rows($pdo,'SELECT l.*,d.hostname FROM beacon_short_links l LEFT JOIN beacon_domains d ON d.id=l.domain_id WHERE l.account_id=:account_id ORDER BY l.created_at DESC',$accountId),
            'pages'=>$this->rows($pdo,'SELECT p.*,d.hostname FROM beacon_pages p LEFT JOIN beacon_domains d ON d.id=p.domain_id WHERE p.account_id=:account_id ORDER BY p.created_at DESC',$accountId),
            'qrs'=>$this->rows($pdo,'SELECT * FROM beacon_qr_definitions WHERE account_id=:account_id ORDER BY created_at DESC',$accountId),
        ];
    }

    public function createLink(string $accountId,string $label,string $destination,?string $sourceDomain=null,?string $sourceReference=null,?string $domainId=null): string
    {
        $label=trim($label);$destination=trim($destination);
        if($label===''||mb_strlen($label)>180)throw new RuntimeException('Give the link a clear label.');
        if(!filter_var($destination,FILTER_VALIDATE_URL))throw new RuntimeException('Enter a valid destination URL.');
        $id=self::uuid();$slug=$this->slug();$domainId=$domainId?:((new BeaconDomainService($this->database))->defaultDomainId());
        $this->database->pdo()->prepare('INSERT INTO beacon_short_links (id,account_id,domain_id,slug,destination_url,label,source_domain,source_reference,status,created_at,updated_at) VALUES (:id,:account_id,:domain_id,:slug,:destination,:label,:domain,:reference,"active",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$id,'account_id'=>$accountId,'domain_id'=>$domainId,'slug'=>$slug,'destination'=>$destination,'label'=>$label,'domain'=>$sourceDomain,'reference'=>$sourceReference]);
        return $id;
    }

    public function createPage(string $accountId,string $type,string $title,string $summary,array $payload,string $visibility='unlisted',?string $sourceDomain=null,?string $sourceReference=null,?string $domainId=null): string
    {
        if(!in_array($type,['link_hub','business_card','wifi','event_landing'],true))throw new RuntimeException('Unsupported Beacon page type.');
        if(!in_array($visibility,['private','unlisted','public'],true))throw new RuntimeException('Unsupported page visibility.');
        $title=trim($title);if($title==='')throw new RuntimeException('Give the page a title.');
        $id=self::uuid();$key=$this->slug();$domainId=$domainId?:((new BeaconDomainService($this->database))->defaultDomainId());
        $this->database->pdo()->prepare('INSERT INTO beacon_pages (id,account_id,domain_id,page_key,page_type,title,summary,payload_json,visibility,source_domain,source_reference,created_at,updated_at) VALUES (:id,:account_id,:domain_id,:page_key,:type,:title,:summary,:payload,:visibility,:domain,:reference,UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$id,'account_id'=>$accountId,'domain_id'=>$domainId,'page_key'=>$key,'type'=>$type,'title'=>$title,'summary'=>$summary,'payload'=>json_encode($payload,JSON_THROW_ON_ERROR),'visibility'=>$visibility,'domain'=>$sourceDomain,'reference'=>$sourceReference]);
        return $id;
    }

    public function createQr(string $accountId,string $targetType,string $targetReference,string $encodedValue,string $label): string
    {
        $id=self::uuid();$this->database->pdo()->prepare('INSERT INTO beacon_qr_definitions (id,account_id,target_type,target_reference,encoded_value,label,created_at) VALUES (:id,:account_id,:type,:reference,:value,:label,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE encoded_value=VALUES(encoded_value),label=VALUES(label)')->execute(['id'=>$id,'account_id'=>$accountId,'type'=>$targetType,'reference'=>$targetReference,'value'=>$encodedValue,'label'=>$label]);return $id;
    }

    public function resolve(string $slug): ?array
    {
        $s=$this->database->pdo()->prepare('SELECT * FROM beacon_short_links WHERE slug=:slug AND status="active" LIMIT 1');$s->execute(['slug'=>$slug]);$row=$s->fetch();if(!$row)return null;$this->database->pdo()->prepare('UPDATE beacon_short_links SET visit_count=visit_count+1,updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['id'=>$row['id']]);return $row;
    }

    public function page(string $key): ?array
    {
        $s=$this->database->pdo()->prepare('SELECT * FROM beacon_pages WHERE page_key=:page_key AND visibility IN ("unlisted","public") LIMIT 1');$s->execute(['page_key'=>$key]);$row=$s->fetch();if(!$row)return null;$row['payload']=json_decode((string)$row['payload_json'],true,512,JSON_THROW_ON_ERROR);return $row;
    }

    public function getLinkById(string $accountId,string $id): ?array
    {
        $s=$this->database->pdo()->prepare('SELECT l.*,d.hostname FROM beacon_short_links l LEFT JOIN beacon_domains d ON d.id=l.domain_id WHERE l.id=:id AND l.account_id=:account_id');$s->execute(['id'=>$id,'account_id'=>$accountId]);return $s->fetch()?:null;
    }

    private function rows(PDO $pdo,string $sql,string $accountId): array{$s=$pdo->prepare($sql);$s->execute(['account_id'=>$accountId]);return $s->fetchAll();}
    private function slug(): string{return strtolower(substr(bin2hex(random_bytes(8)),0,10));}
    private static function uuid(): string{$d=random_bytes(16);$d[6]=chr((ord($d[6])&0x0f)|0x40);$d[8]=chr((ord($d[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4));}
}