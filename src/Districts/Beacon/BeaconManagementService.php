<?php

declare(strict_types=1);

namespace Koravik\Districts\Beacon;

use Koravik\Platform\Database\Database;
use RuntimeException;

final class BeaconManagementService
{
    public function __construct(private readonly Database $database) {}

    public function domains(): array { return $this->database->pdo()->query('SELECT * FROM beacon_domains ORDER BY is_default DESC,hostname')->fetchAll(); }
    public function links(string $accountId): array { $s=$this->database->pdo()->prepare('SELECT l.*,d.hostname FROM beacon_short_links l LEFT JOIN beacon_domains d ON d.id=l.domain_id WHERE l.account_id=:account ORDER BY l.created_at DESC');$s->execute(['account'=>$accountId]);return $s->fetchAll(); }

    public function registerDomain(string $accountId,string $hostname,string $rootRedirect): string
    {
        $hostname=strtolower(trim($hostname));if(!preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/',$hostname))throw new RuntimeException('Enter a valid hostname.');if($rootRedirect!==''&&!filter_var($rootRedirect,FILTER_VALIDATE_URL))throw new RuntimeException('Enter a valid root redirect URL.');$token=bin2hex(random_bytes(24));$id=self::uuid();$this->database->pdo()->prepare('INSERT INTO beacon_domains (id,hostname,domain_type,owner_type,owner_id,root_redirect_url,verification_status,verification_token,is_default,created_at,updated_at) VALUES (:id,:host,"organization","account",:owner,:redirect,"pending",:token,0,UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$id,'host'=>$hostname,'owner'=>$accountId,'redirect'=>$rootRedirect?:null,'token'=>$token]);$this->revision('domain',$id,$accountId,'registered',['hostname'=>$hostname]);return $token;
    }

    public function verifyDomain(string $accountId,string $id,string $token): void
    {
        $s=$this->database->pdo()->prepare('UPDATE beacon_domains SET verification_status="verified",verified_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE id=:id AND owner_id=:owner AND verification_token=:token AND verification_status="pending"');$s->execute(['id'=>$id,'owner'=>$accountId,'token'=>trim($token)]);if($s->rowCount()!==1)throw new RuntimeException('Verification token did not match.');$this->revision('domain',$id,$accountId,'verified',[]);
    }

    public function setDomainState(string $accountId,string $id,string $state): void
    {
        if(!in_array($state,['verified','suspended'],true))throw new RuntimeException('Unsupported domain state.');$s=$this->database->pdo()->prepare('UPDATE beacon_domains SET verification_status=:state,suspended_at=IF(:state2="suspended",UTC_TIMESTAMP(),NULL),updated_at=UTC_TIMESTAMP() WHERE id=:id AND (owner_id=:owner OR owner_type="platform")');$s->execute(['state'=>$state,'state2'=>$state,'id'=>$id,'owner'=>$accountId]);if($s->rowCount()!==1)throw new RuntimeException('Domain was not found.');$this->revision('domain',$id,$accountId,$state,[]);
    }

    public function updateLink(string $accountId,string $id,array $input): void
    {
        $s=$this->database->pdo()->prepare('SELECT * FROM beacon_short_links WHERE id=:id AND account_id=:account');$s->execute(['id'=>$id,'account'=>$accountId]);$before=$s->fetch();if(!$before)throw new RuntimeException('Beacon link not found.');$label=trim((string)($input['label']??''));$destination=trim((string)($input['destination']??''));$slug=strtolower(trim((string)($input['slug']??'')));if($label===''||!filter_var($destination,FILTER_VALIDATE_URL))throw new RuntimeException('Label and valid destination are required.');if(!preg_match('/^[a-z0-9][a-z0-9-]{2,63}$/',$slug)||in_array($slug,['admin','api','health','login','register','system'],true))throw new RuntimeException('Choose a different slug.');$domain=(string)($input['domain_id']??$before['domain_id']);$u=$this->database->pdo()->prepare('UPDATE beacon_short_links SET label=:label,destination_url=:destination,slug=:slug,domain_id=:domain,preferred_domain_id=:domain2,updated_at=UTC_TIMESTAMP() WHERE id=:id AND account_id=:account');$u->execute(['label'=>$label,'destination'=>$destination,'slug'=>$slug,'domain'=>$domain,'domain2'=>$domain,'id'=>$id,'account'=>$accountId]);$this->revision('link',$id,$accountId,'updated',['before'=>$before,'after'=>['label'=>$label,'destination_url'=>$destination,'slug'=>$slug,'domain_id'=>$domain]]);
    }

    public function setLinkState(string $accountId,string $id,string $state): void
    {
        if(!in_array($state,['active','paused','archived'],true))throw new RuntimeException('Unsupported link state.');$s=$this->database->pdo()->prepare('UPDATE beacon_short_links SET status=:state,archived_at=IF(:state2="archived",UTC_TIMESTAMP(),NULL),updated_at=UTC_TIMESTAMP() WHERE id=:id AND account_id=:account');$s->execute(['state'=>$state,'state2'=>$state,'id'=>$id,'account'=>$accountId]);if($s->rowCount()!==1)throw new RuntimeException('Beacon link not found.');$this->revision('link',$id,$accountId,$state,[]);
    }

    private function revision(string $type,string $id,string $account,string $action,array $context): void
    {
        $table=$type==='domain'?'beacon_domain_revisions':'beacon_link_revisions';$columns=$type==='domain'?'(id,domain_id,changed_by_account_id,action,context_json,created_at)':'(id,link_id,changed_by_account_id,action,after_json,created_at)';$this->database->pdo()->prepare('INSERT INTO '.$table.' '.$columns.' VALUES (:rid,:id,:account,:action,:context,UTC_TIMESTAMP())')->execute(['rid'=>self::uuid(),'id'=>$id,'account'=>$account,'action'=>$action,'context'=>json_encode($context,JSON_THROW_ON_ERROR)]);
    }

    private static function uuid(): string{$d=random_bytes(16);$d[6]=chr((ord($d[6])&0x0f)|0x40);$d[8]=chr((ord($d[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4));}
}
