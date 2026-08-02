<?php

declare(strict_types=1);

namespace Koravik\Districts\Beacon;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class BeaconService
{
    public function __construct(private readonly Database $database) {}

    public const BEACON_ACTION_TYPES=['link','text','email','call','sms','vcard','whatsapp','wifi','pdf','app','image','video','social_media','event','barcode_2d'];

    public function dashboard(string $accountId): array
    {
        $pdo=$this->database->pdo();
        return [
            'links'=>$this->rows($pdo,'SELECT l.*,d.hostname FROM beacon_short_links l LEFT JOIN beacon_domains d ON d.id=l.domain_id WHERE l.account_id=:account_id ORDER BY l.created_at DESC',$accountId),
            'pages'=>$this->rows($pdo,'SELECT p.*,d.hostname FROM beacon_pages p LEFT JOIN beacon_domains d ON d.id=p.domain_id WHERE p.account_id=:account_id ORDER BY p.created_at DESC',$accountId),
            'qrs'=>$this->rows($pdo,'SELECT * FROM beacon_qr_definitions WHERE account_id=:account_id ORDER BY created_at DESC',$accountId),
            'campaigns'=>$this->rows($pdo,'SELECT c.*,p.title page_title,l.label link_label FROM beacon_campaigns c LEFT JOIN beacon_pages p ON p.id=c.page_id LEFT JOIN beacon_short_links l ON l.id=c.short_link_id WHERE c.account_id=:account_id ORDER BY c.updated_at DESC',$accountId),
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
        $s=$this->database->pdo()->prepare('SELECT * FROM beacon_pages WHERE page_key=:page_key AND visibility IN ("unlisted","public") LIMIT 1');$s->execute(['page_key'=>$key]);$row=$s->fetch();if(!$row)return null;$row['payload']=json_decode((string)$row['payload_json'],true,512,JSON_THROW_ON_ERROR);$b=$this->database->pdo()->prepare('SELECT * FROM beacon_page_blocks WHERE page_id=:id ORDER BY sort_order,created_at');$b->execute(['id'=>$row['id']]);$row['blocks']=$b->fetchAll();return $row;
    }

    public function ownedPage(string $accountId,string $id): ?array
    {
        $s=$this->database->pdo()->prepare('SELECT * FROM beacon_pages WHERE id=:id AND account_id=:account_id LIMIT 1');$s->execute(['id'=>$id,'account_id'=>$accountId]);$row=$s->fetch();if(!$row)return null;$row['payload']=json_decode((string)$row['payload_json'],true,512,JSON_THROW_ON_ERROR);$r=$this->database->pdo()->prepare('SELECT action,created_at FROM beacon_page_revisions WHERE page_id=:id ORDER BY created_at DESC LIMIT 10');$r->execute(['id'=>$id]);$row['revisions']=$r->fetchAll();
        $b=$this->database->pdo()->prepare('SELECT * FROM beacon_page_blocks WHERE page_id=:id ORDER BY sort_order,created_at');$b->execute(['id'=>$id]);$row['blocks']=$b->fetchAll();
        return $row;
    }

    public function updatePage(string $accountId,string $id,array $input): void
    {
        $page=$this->ownedPage($accountId,$id);if(!$page)throw new RuntimeException('Beacon page not found.');$title=trim((string)($input['title']??''));$summary=trim((string)($input['summary']??''));$value=trim((string)($input['value']??''));$visibility=(string)($input['visibility']??'private');if($title===''||mb_strlen($title)>180)throw new RuntimeException('Use a page title between 1 and 180 characters.');if(!in_array($visibility,['private','unlisted','public'],true))throw new RuntimeException('Choose a valid visibility.');$payload=$page['payload'];$payload['value']=$value;
        if(in_array($visibility,['unlisted','public'],true)&&$summary===''&&$value===''&&empty($page['blocks']))throw new RuntimeException('Publishing checks require at least summary, primary value, or one public block before the page leaves private draft.');
        $this->database->transaction(function(PDO $pdo)use($accountId,$id,$title,$summary,$visibility,$payload):void{$pdo->prepare('UPDATE beacon_pages SET title=:title,summary=:summary,payload_json=:payload,visibility=:visibility,updated_at=UTC_TIMESTAMP() WHERE id=:id AND account_id=:account_id')->execute(['title'=>$title,'summary'=>$summary,'payload'=>json_encode($payload,JSON_THROW_ON_ERROR),'visibility'=>$visibility,'id'=>$id,'account_id'=>$accountId]);$pdo->prepare('INSERT INTO beacon_page_revisions (id,page_id,changed_by_account_id,action,snapshot_json,created_at) VALUES (:id,:page,:account,"updated",:snapshot,UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'page'=>$id,'account'=>$accountId,'snapshot'=>json_encode(['title'=>$title,'summary'=>$summary,'visibility'=>$visibility,'payload'=>$payload],JSON_THROW_ON_ERROR)]);});
    }

    public function addBlock(string $accountId,string $pageId,array $input): void
    {
        $page=$this->ownedPage($accountId,$pageId);if(!$page)throw new RuntimeException('Beacon page not found.');
        $type=(string)($input['block_type']??'text');if(!in_array($type,self::BEACON_ACTION_TYPES,true))throw new RuntimeException('Choose a valid Beacon type.');
        $title=trim((string)($input['title']??''));$body=trim((string)($input['body']??''));$label=trim((string)($input['action_label']??''));$value=trim((string)($input['action_value']??''));if($title===''&&$body===''&&$value==='')throw new RuntimeException('Add text, a title, or an action value.');
        $order=(int)$this->database->pdo()->query('SELECT COALESCE(MAX(sort_order),0)+10 FROM beacon_page_blocks WHERE page_id='.$this->database->pdo()->quote($pageId))->fetchColumn();
        $this->database->transaction(function(PDO $pdo)use($accountId,$pageId,$type,$title,$body,$label,$value,$order):void{$pdo->prepare('INSERT INTO beacon_page_blocks (id,page_id,block_type,sort_order,title,body,action_label,action_value,visibility,created_at,updated_at) VALUES (:id,:page,:type,:sort,:title,:body,:label,:value,"private",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'page'=>$pageId,'type'=>$type,'sort'=>$order,'title'=>$title?:null,'body'=>$body?:null,'label'=>$label?:null,'value'=>$value?:null]);$pdo->prepare('INSERT INTO beacon_page_revisions (id,page_id,changed_by_account_id,action,snapshot_json,created_at) VALUES (:id,:page,:account,"block_added",:snapshot,UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'page'=>$pageId,'account'=>$accountId,'snapshot'=>json_encode(['block_type'=>$type,'title'=>$title],JSON_THROW_ON_ERROR)]);});
    }
    public function moveBlock(string $accountId,string $pageId,string $blockId,string $direction): void
    {
        if(!in_array($direction,['up','down'],true))throw new RuntimeException('Choose a valid block move.');
        $page=$this->ownedPage($accountId,$pageId);if(!$page)throw new RuntimeException('Beacon page not found.');
        $blocks=$page['blocks'];$index=null;foreach($blocks as $i=>$b)if((string)$b['id']===$blockId)$index=$i;if($index===null)throw new RuntimeException('Block not found.');
        $swap=$direction==='up'?$index-1:$index+1;if(!isset($blocks[$swap]))return;
        $this->database->transaction(function(PDO $pdo)use($accountId,$pageId,$blocks,$index,$swap):void{$pdo->prepare('UPDATE beacon_page_blocks SET sort_order=:sort,updated_at=UTC_TIMESTAMP() WHERE id=:id AND page_id=:page')->execute(['sort'=>$blocks[$swap]['sort_order'],'id'=>$blocks[$index]['id'],'page'=>$pageId]);$pdo->prepare('UPDATE beacon_page_blocks SET sort_order=:sort,updated_at=UTC_TIMESTAMP() WHERE id=:id AND page_id=:page')->execute(['sort'=>$blocks[$index]['sort_order'],'id'=>$blocks[$swap]['id'],'page'=>$pageId]);$pdo->prepare('INSERT INTO beacon_page_revisions (id,page_id,changed_by_account_id,action,snapshot_json,created_at) VALUES (:id,:page,:account,"block_reordered",JSON_OBJECT("block_id",:block),UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'page'=>$pageId,'account'=>$accountId,'block'=>$blocks[$index]['id']]);});
    }

    public function getLinkById(string $accountId,string $id): ?array
    {
        $s=$this->database->pdo()->prepare('SELECT l.*,d.hostname FROM beacon_short_links l LEFT JOIN beacon_domains d ON d.id=l.domain_id WHERE l.id=:id AND l.account_id=:account_id');$s->execute(['id'=>$id,'account_id'=>$accountId]);return $s->fetch()?:null;
    }

    public function createCampaign(string $accountId,array $input): string
    {
        $title=trim((string)($input['title']??''));$purpose=trim((string)($input['purpose']??''));$audience=trim((string)($input['audience']??''));$pageId=trim((string)($input['page_id']??''))?:null;$linkId=trim((string)($input['short_link_id']??''))?:null;
        if($title===''||mb_strlen($title)>180)throw new RuntimeException('Give the campaign a clear title.');
        if($pageId!==null){$check=$this->database->pdo()->prepare('SELECT id FROM beacon_pages WHERE id=:id AND account_id=:account_id');$check->execute(['id'=>$pageId,'account_id'=>$accountId]);if(!$check->fetchColumn())throw new RuntimeException('Choose one of your Beacon pages.');}
        if($linkId!==null){$check=$this->database->pdo()->prepare('SELECT id FROM beacon_short_links WHERE id=:id AND account_id=:account_id');$check->execute(['id'=>$linkId,'account_id'=>$accountId]);if(!$check->fetchColumn())throw new RuntimeException('Choose one of your Beacon links.');}
        $id=self::uuid();$this->database->transaction(function(PDO $pdo)use($id,$accountId,$title,$purpose,$audience,$pageId,$linkId):void{$pdo->prepare('INSERT INTO beacon_campaigns (id,account_id,page_id,short_link_id,title,purpose,audience,status,created_at,updated_at) VALUES (:id,:account_id,:page_id,:link_id,:title,:purpose,:audience,"draft",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$id,'account_id'=>$accountId,'page_id'=>$pageId,'link_id'=>$linkId,'title'=>$title,'purpose'=>$purpose?:null,'audience'=>$audience?:null]);$this->audit($pdo,$accountId,'beacon.campaign.created',$id);});return $id;
    }

    public function campaign(string $accountId,string $id): array
    {
        $s=$this->database->pdo()->prepare('SELECT c.*,p.title page_title,p.visibility page_visibility,p.page_key,l.label link_label,l.visit_count FROM beacon_campaigns c LEFT JOIN beacon_pages p ON p.id=c.page_id LEFT JOIN beacon_short_links l ON l.id=c.short_link_id WHERE c.id=:id AND c.account_id=:account_id LIMIT 1');$s->execute(['id'=>$id,'account_id'=>$accountId]);$row=$s->fetch();if(!$row)throw new RuntimeException('Beacon campaign not found.');return $row;
    }

    public function updateCampaign(string $accountId,string $id,array $input): void
    {
        $title=trim((string)($input['title']??''));$purpose=trim((string)($input['purpose']??''));$audience=trim((string)($input['audience']??''));$status=(string)($input['status']??'draft');
        if($title===''||mb_strlen($title)>180)throw new RuntimeException('Give the campaign a clear title.');
        if(!in_array($status,['draft','active','paused','archived'],true))throw new RuntimeException('Choose a valid campaign status.');
        $this->database->transaction(function(PDO $pdo)use($accountId,$id,$title,$purpose,$audience,$status):void{$u=$pdo->prepare('UPDATE beacon_campaigns SET title=:title,purpose=:purpose,audience=:audience,status=:status,updated_at=UTC_TIMESTAMP() WHERE id=:id AND account_id=:account_id');$u->execute(['title'=>$title,'purpose'=>$purpose?:null,'audience'=>$audience?:null,'status'=>$status,'id'=>$id,'account_id'=>$accountId]);if($u->rowCount()!==1)throw new RuntimeException('Beacon campaign not found.');$this->audit($pdo,$accountId,'beacon.campaign.updated',$id);});
    }

    private function audit(PDO $pdo,string $accountId,string $action,string $subject): void{$pdo->prepare('INSERT INTO audit_log (id,account_id,action,subject_type,subject_id,occurred_at) VALUES (:id,:account_id,:action,"beacon_campaign",:subject,UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'action'=>$action,'subject'=>$subject]);}

    private function rows(PDO $pdo,string $sql,string $accountId): array{$s=$pdo->prepare($sql);$s->execute(['account_id'=>$accountId]);return $s->fetchAll();}
    private function slug(): string{return strtolower(substr(bin2hex(random_bytes(8)),0,10));}
    private static function uuid(): string{$d=random_bytes(16);$d[6]=chr((ord($d[6])&0x0f)|0x40);$d[8]=chr((ord($d[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4));}
}
