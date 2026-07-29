<?php

declare(strict_types=1);

namespace Koravik\Platform\Organizations;

use Koravik\Platform\Database\Database;
use RuntimeException;

final class OrganizationService
{
    public function __construct(private readonly Database $database) {}

    public function memberships(string $accountId): array
    {
        $s=$this->database->pdo()->prepare('SELECT o.*,m.role membership_role,m.status membership_status FROM organization_memberships m JOIN organizations o ON o.id=m.organization_id WHERE m.account_id=:account AND m.status="active" AND o.status="active" ORDER BY o.name');$s->execute(['account'=>$accountId]);return $s->fetchAll();
    }

    public function create(string $accountId,array $input): string
    {
        $name=trim((string)($input['name']??''));if($name===''||mb_strlen($name)>180)throw new RuntimeException('Give the organization a clear name.');$id=self::uuid();$membership=self::uuid();$timezone=trim((string)($input['timezone']??'UTC'))?:'UTC';
        $this->database->transaction(function($pdo)use($id,$membership,$accountId,$name,$timezone,$input):void{$pdo->prepare('INSERT INTO organizations (id,name,summary,primary_timezone,status,created_by_account_id,created_at,updated_at) VALUES (:id,:name,:summary,:timezone,"active",:account,UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$id,'name'=>$name,'summary'=>trim((string)($input['summary']??'')),'timezone'=>$timezone,'account'=>$accountId]);$pdo->prepare('INSERT INTO organization_memberships (id,organization_id,account_id,role,status,joined_at,created_at,updated_at) VALUES (:id,:organization,:account,"owner","active",UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$membership,'organization'=>$id,'account'=>$accountId]);$this->activity($pdo,$id,$accountId,'organization.created','organization',$id,['name'=>$name]);});return $id;
    }

    public function organization(string $accountId,string $id): array
    {
        $s=$this->database->pdo()->prepare('SELECT o.*,m.role membership_role FROM organizations o JOIN organization_memberships m ON m.organization_id=o.id WHERE o.id=:id AND m.account_id=:account AND m.status="active" LIMIT 1');$s->execute(['id'=>$id,'account'=>$accountId]);$org=$s->fetch();if(!$org)throw new RuntimeException('Organization not found.');return $org;
    }

    public function dashboard(string $accountId,string $id): array
    {
        $org=$this->organization($accountId,$id);$pdo=$this->database->pdo();$members=$pdo->prepare('SELECT m.*,a.display_name,a.email FROM organization_memberships m JOIN platform_accounts a ON a.id=m.account_id WHERE m.organization_id=:id ORDER BY FIELD(m.role,"owner","admin","creator","member"),a.display_name');$members->execute(['id'=>$id]);$events=$pdo->prepare('SELECT id,title,starts_at,status,lifecycle_status FROM gather_events WHERE owner_type="organization" AND organization_id=:id ORDER BY starts_at DESC LIMIT 12');$events->execute(['id'=>$id]);$links=$pdo->prepare('SELECT l.*,d.hostname FROM beacon_short_links l LEFT JOIN beacon_domains d ON d.id=l.domain_id WHERE l.owner_type="organization" AND l.organization_id=:id ORDER BY l.created_at DESC LIMIT 12');$links->execute(['id'=>$id]);$activity=$pdo->prepare('SELECT * FROM organization_activity WHERE organization_id=:id ORDER BY created_at DESC LIMIT 20');$activity->execute(['id'=>$id]);$invites=$pdo->prepare('SELECT id,email,role,status,expires_at FROM organization_invitations WHERE organization_id=:id AND status="pending" ORDER BY created_at DESC');$invites->execute(['id'=>$id]);return ['organization'=>$org,'members'=>$members->fetchAll(),'events'=>$events->fetchAll(),'links'=>$links->fetchAll(),'activity'=>$activity->fetchAll(),'invitations'=>$invites->fetchAll()];
    }

    public function can(string $accountId,string $organizationId,string $capability): bool
    {
        $s=$this->database->pdo()->prepare('SELECT role FROM organization_memberships WHERE organization_id=:organization AND account_id=:account AND status="active" LIMIT 1');$s->execute(['organization'=>$organizationId,'account'=>$accountId]);$role=$s->fetchColumn();if(!$role)return false;$map=['view'=>['owner','admin','creator','member'],'create_content'=>['owner','admin','creator'],'manage_content'=>['owner','admin','creator'],'manage_members'=>['owner','admin'],'manage_domains'=>['owner','admin'],'manage_organization'=>['owner']];return in_array((string)$role,$map[$capability]??[],true);
    }

    public function invite(string $actorId,string $organizationId,string $email,string $role): string
    {
        if(!$this->can($actorId,$organizationId,'manage_members'))throw new RuntimeException('You cannot manage members for this organization.');$email=strtolower(trim($email));if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Enter a valid email address.');if(!in_array($role,['admin','creator','member'],true))$role='member';$raw=self::token();$id=self::uuid();$this->database->pdo()->prepare('INSERT INTO organization_invitations (id,organization_id,email,role,token_hash,status,invited_by_account_id,expires_at,created_at,updated_at) VALUES (:id,:organization,:email,:role,:hash,"pending",:actor,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 14 DAY),UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$id,'organization'=>$organizationId,'email'=>$email,'role'=>$role,'hash'=>hash('sha256',$raw),'actor'=>$actorId]);$this->activity($this->database->pdo(),$organizationId,$actorId,'membership.invited','invitation',$id,['email'=>$email,'role'=>$role]);return $raw;
    }

    public function acceptInvitation(string $accountId,string $token): string
    {
        $hash=hash('sha256',$token);$pdo=$this->database->pdo();$s=$pdo->prepare('SELECT i.*,a.email account_email FROM organization_invitations i JOIN platform_accounts a ON a.id=:account WHERE i.token_hash=:hash AND i.status="pending" AND i.expires_at>UTC_TIMESTAMP() LIMIT 1');$s->execute(['account'=>$accountId,'hash'=>$hash]);$invite=$s->fetch();if(!$invite||strtolower((string)$invite['account_email'])!==strtolower((string)$invite['email']))throw new RuntimeException('That invitation is unavailable for this account.');$this->database->transaction(function($pdo)use($invite,$accountId):void{$pdo->prepare('INSERT INTO organization_memberships (id,organization_id,account_id,role,status,invited_by_account_id,joined_at,created_at,updated_at) VALUES (:id,:organization,:account,:role,"active",:inviter,UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE role=VALUES(role),status="active",ended_at=NULL,updated_at=UTC_TIMESTAMP()')->execute(['id'=>self::uuid(),'organization'=>$invite['organization_id'],'account'=>$accountId,'role'=>$invite['role'],'inviter'=>$invite['invited_by_account_id']]);$pdo->prepare('UPDATE organization_invitations SET status="accepted",accepted_by_account_id=:account,updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['account'=>$accountId,'id'=>$invite['id']]);$this->activity($pdo,(string)$invite['organization_id'],$accountId,'membership.accepted','membership',null,['role'=>$invite['role']]);});return (string)$invite['organization_id'];
    }

    public function changeRole(string $actorId,string $organizationId,string $memberId,string $role): void
    {
        if(!$this->can($actorId,$organizationId,'manage_members'))throw new RuntimeException('You cannot manage members.');if(!in_array($role,['admin','creator','member'],true))throw new RuntimeException('Choose a valid role.');$s=$this->database->pdo()->prepare('UPDATE organization_memberships SET role=:role,updated_at=UTC_TIMESTAMP() WHERE id=:id AND organization_id=:organization AND role<>"owner" AND status="active"');$s->execute(['role'=>$role,'id'=>$memberId,'organization'=>$organizationId]);if($s->rowCount()!==1)throw new RuntimeException('That membership cannot be changed.');$this->activity($this->database->pdo(),$organizationId,$actorId,'membership.role_changed','membership',$memberId,['role'=>$role]);
    }

    public function removeMember(string $actorId,string $organizationId,string $memberId): void
    {
        if(!$this->can($actorId,$organizationId,'manage_members'))throw new RuntimeException('You cannot manage members.');$s=$this->database->pdo()->prepare('UPDATE organization_memberships SET status="removed",ended_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE id=:id AND organization_id=:organization AND role<>"owner" AND status="active"');$s->execute(['id'=>$memberId,'organization'=>$organizationId]);if($s->rowCount()!==1)throw new RuntimeException('That membership cannot be removed.');$this->activity($this->database->pdo(),$organizationId,$actorId,'membership.removed','membership',$memberId,[]);
    }

    public function transferOwnership(string $actorId,string $organizationId,string $membershipId): void
    {
        if(!$this->can($actorId,$organizationId,'manage_organization'))throw new RuntimeException('Only the organization Owner may transfer ownership.');$pdo=$this->database->pdo();$target=$pdo->prepare('SELECT account_id FROM organization_memberships WHERE id=:id AND organization_id=:organization AND status="active" LIMIT 1');$target->execute(['id'=>$membershipId,'organization'=>$organizationId]);if(!$target->fetchColumn())throw new RuntimeException('Choose an active member.');$this->database->transaction(function($pdo)use($actorId,$organizationId,$membershipId):void{$pdo->prepare('UPDATE organization_memberships SET role="admin",updated_at=UTC_TIMESTAMP() WHERE organization_id=:organization AND account_id=:actor AND role="owner"')->execute(['organization'=>$organizationId,'actor'=>$actorId]);$pdo->prepare('UPDATE organization_memberships SET role="owner",updated_at=UTC_TIMESTAMP() WHERE id=:id AND organization_id=:organization')->execute(['id'=>$membershipId,'organization'=>$organizationId]);$this->activity($pdo,$organizationId,$actorId,'organization.ownership_transferred','membership',$membershipId,[]);});
    }

    public function createOrganizationEvent(string $actorId,string $organizationId,array $input): string
    {
        if(!$this->can($actorId,$organizationId,'create_content'))throw new RuntimeException('You cannot create organization events.');$id=(new \Koravik\Districts\Gather\GatherService($this->database))->createEvent($actorId,$input);$this->database->pdo()->prepare('UPDATE gather_events SET owner_type="organization",organization_id=:organization,updated_at=UTC_TIMESTAMP() WHERE id=:id AND account_id=:actor')->execute(['organization'=>$organizationId,'id'=>$id,'actor'=>$actorId]);$this->activity($this->database->pdo(),$organizationId,$actorId,'gather.event_created','gather_event',$id,[]);return $id;
    }

    public function createOrganizationLink(string $actorId,string $organizationId,string $label,string $destination): string
    {
        if(!$this->can($actorId,$organizationId,'create_content'))throw new RuntimeException('You cannot create organization links.');$id=(new \Koravik\Districts\Beacon\BeaconService($this->database))->createLink($actorId,$label,$destination,'organization',$organizationId);$this->database->pdo()->prepare('UPDATE beacon_short_links SET owner_type="organization",organization_id=:organization,updated_at=UTC_TIMESTAMP() WHERE id=:id AND account_id=:actor')->execute(['organization'=>$organizationId,'id'=>$id,'actor'=>$actorId]);$this->activity($this->database->pdo(),$organizationId,$actorId,'beacon.link_created','beacon_link',$id,[]);return $id;
    }

    private function activity($pdo,string $organizationId,string $actor,string $action,string $subjectType,?string $subjectId,array $context):void{$pdo->prepare('INSERT INTO organization_activity (id,organization_id,actor_account_id,action,subject_type,subject_id,context_json,created_at) VALUES (:id,:organization,:actor,:action,:type,:subject,:context,UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'organization'=>$organizationId,'actor'=>$actor,'action'=>$action,'type'=>$subjectType,'subject'=>$subjectId,'context'=>json_encode($context,JSON_THROW_ON_ERROR)]);}
    private static function token():string{return rtrim(strtr(base64_encode(random_bytes(32)),'+/','-_'),'=');}
    private static function uuid():string{$d=random_bytes(16);$d[6]=chr((ord($d[6])&0x0f)|0x40);$d[8]=chr((ord($d[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4));}
}
