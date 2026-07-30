<?php

declare(strict_types=1);

namespace Koravik\Platform\Organizations;

use Koravik\Districts\Beacon\BeaconService;
use Koravik\Districts\Quests\QuestService;
use Koravik\Platform\Database\Database;
use Koravik\Platform\Mail\MailQueue;
use RuntimeException;

final class OrganizationExpansionService
{
    public function __construct(private readonly Database $database) {}

    public function revokeInvitation(string $actor,string $organization,string $invitation):void
    {
        $this->require($actor,$organization,'manage_members');$s=$this->database->pdo()->prepare('UPDATE organization_invitations SET status="revoked",revoked_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE id=:id AND organization_id=:organization AND status="pending"');$s->execute(['id'=>$invitation,'organization'=>$organization]);if($s->rowCount()!==1)throw new RuntimeException('That invitation is no longer pending.');$this->activity($organization,$actor,'membership.invitation_revoked','invitation',$invitation,[]);
    }

    public function resendInvitation(string $actor,string $organization,string $invitation,string $baseUrl):void
    {
        $this->require($actor,$organization,'manage_members');$pdo=$this->database->pdo();$s=$pdo->prepare('SELECT i.*,o.name organization_name FROM organization_invitations i JOIN organizations o ON o.id=i.organization_id WHERE i.id=:id AND i.organization_id=:organization AND i.status="pending" AND i.expires_at>UTC_TIMESTAMP()');$s->execute(['id'=>$invitation,'organization'=>$organization]);$i=$s->fetch();if(!$i)throw new RuntimeException('That invitation cannot be resent.');$raw=self::token();$path='/organizations/invitations/'.$raw;$name=self::e((string)$i['organization_name']);$url=rtrim($baseUrl,'/').$path;$delivery=(new MailQueue($this->database))->enqueue('organization.invitation',(string)$i['email'],'','Invitation to '.$i['organization_name'],'<h1>You are invited to '.$name.'</h1><p><a href="'.self::e($url).'">Review invitation</a></p>','You are invited to '.$i['organization_name'].'. Review: '.$url,null,null,null,(string)($i['delivery_id']??'')?:null);$pdo->prepare('UPDATE organization_invitations SET token_hash=:hash,delivery_id=:delivery,last_sent_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['hash'=>hash('sha256',$raw),'delivery'=>$delivery,'id'=>$invitation]);$this->activity($organization,$actor,'membership.invitation_resent','invitation',$invitation,['delivery_id'=>$delivery]);
    }

    public function updateSettings(string $actor,string $organization,array $input):void
    {
        $this->require($actor,$organization,'manage_organization');$name=trim((string)($input['name']??''));$email=strtolower(trim((string)($input['contact_email']??'')));$color=trim((string)($input['brand_color']??''));if($name===''||mb_strlen($name)>180)throw new RuntimeException('Give the organization a clear name.');if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Enter a valid contact email.');if($color!==''&&!preg_match('/^#[0-9a-fA-F]{6}$/',$color))throw new RuntimeException('Brand color must be a six-digit hex color.');$this->database->pdo()->prepare('UPDATE organizations SET name=:name,public_name=:public_name,summary=:summary,contact_email=:email,brand_color=:color,primary_timezone=:timezone,updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['name'=>$name,'public_name'=>trim((string)($input['public_name']??''))?:null,'summary'=>trim((string)($input['summary']??'')),'email'=>$email?:null,'color'=>$color?:null,'timezone'=>trim((string)($input['timezone']??'UTC'))?:'UTC','id'=>$organization]);$this->activity($organization,$actor,'organization.settings_updated','organization',$organization,[]);
    }

    public function lifecycle(string $actor,string $organization,string $state):void
    {
        $this->require($actor,$organization,'manage_organization');if(!in_array($state,['active','suspended','archived'],true))throw new RuntimeException('Choose a valid lifecycle state.');$pdo=$this->database->pdo();$s=$pdo->prepare('SELECT status FROM organizations WHERE id=:id');$s->execute(['id'=>$organization]);$before=$s->fetchColumn();if(!$before)throw new RuntimeException('Organization not found.');$pdo->prepare('UPDATE organizations SET status=:status,suspended_at=IF(:suspended="suspended",UTC_TIMESTAMP(),NULL),archived_at=IF(:archived="archived",UTC_TIMESTAMP(),NULL),updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['status'=>$state,'suspended'=>$state,'archived'=>$state,'id'=>$organization]);$pdo->prepare('INSERT INTO organization_recovery_records (id,organization_id,actor_account_id,action,previous_status,new_status,created_at) VALUES (:id,:organization,:actor,:action,:previous,:new,UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'organization'=>$organization,'actor'=>$actor,'action'=>'organization.lifecycle_changed','previous'=>$before,'new'=>$state]);$this->activity($organization,$actor,'organization.lifecycle_changed','organization',$organization,['previous'=>$before,'new'=>$state]);
    }

    public function assignDomain(string $actor,string $organization,string $domain):void
    {
        $this->require($actor,$organization,'manage_domains');$s=$this->database->pdo()->prepare('SELECT id FROM beacon_domains WHERE id=:id AND verification_status="verified" AND (organization_id=:organization OR owner_type="platform")');$s->execute(['id'=>$domain,'organization'=>$organization]);if(!$s->fetchColumn())throw new RuntimeException('Choose a verified Organization or platform domain.');$this->database->pdo()->prepare('UPDATE organizations SET beacon_domain_id=:domain,updated_at=UTC_TIMESTAMP() WHERE id=:organization')->execute(['domain'=>$domain,'organization'=>$organization]);$this->activity($organization,$actor,'organization.domain_assigned','beacon_domain',$domain,[]);
    }

    public function createTeam(string $actor,string $organization,string $name,string $summary):void
    {
        $this->require($actor,$organization,'manage_members');$name=trim($name);if($name===''||mb_strlen($name)>180)throw new RuntimeException('Give the team a clear name.');$id=self::uuid();$this->database->pdo()->prepare('INSERT INTO organization_teams (id,organization_id,name,summary,status,created_by_account_id,created_at,updated_at) VALUES (:id,:organization,:name,:summary,"active",:actor,UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$id,'organization'=>$organization,'name'=>$name,'summary'=>trim($summary),'actor'=>$actor]);$this->activity($organization,$actor,'organization.team_created','team',$id,['name'=>$name]);
    }

    public function addTeamMember(string $actor,string $organization,string $team,string $membership,string $role):void
    {
        $this->require($actor,$organization,'manage_members');$role=$role==='lead'?'lead':'member';$s=$this->database->pdo()->prepare('SELECT 1 FROM organization_teams t JOIN organization_memberships m ON m.organization_id=t.organization_id WHERE t.id=:team AND t.organization_id=:organization AND m.id=:membership AND m.status="active"');$s->execute(['team'=>$team,'organization'=>$organization,'membership'=>$membership]);if(!$s->fetchColumn())throw new RuntimeException('Choose an active team and member.');$this->database->pdo()->prepare('INSERT INTO organization_team_memberships (id,team_id,membership_id,team_role,created_at) VALUES (:id,:team,:membership,:role,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE team_role=VALUES(team_role)')->execute(['id'=>self::uuid(),'team'=>$team,'membership'=>$membership,'role'=>$role]);$this->activity($organization,$actor,'organization.team_member_added','team',$team,['membership_id'=>$membership,'role'=>$role]);
    }

    public function publishPage(string $actor,string $organization,string $visibility):string
    {
        $this->require($actor,$organization,'manage_content');$s=$this->database->pdo()->prepare('SELECT * FROM organizations WHERE id=:id');$s->execute(['id'=>$organization]);$o=$s->fetch();if(!$o)throw new RuntimeException('Organization not found.');$links=$this->database->pdo()->prepare('SELECT label,destination_url FROM beacon_short_links WHERE organization_id=:id AND status="active" ORDER BY created_at DESC LIMIT 12');$links->execute(['id'=>$organization]);$events=$this->database->pdo()->prepare('SELECT id,title,starts_at FROM gather_events WHERE organization_id=:id AND visibility="public" AND starts_at>=UTC_TIMESTAMP() ORDER BY starts_at LIMIT 12');$events->execute(['id'=>$organization]);$id=(new BeaconService($this->database))->createPage($actor,'link_hub',(string)($o['public_name']?:$o['name']),(string)$o['summary'],['organization_id'=>$organization,'links'=>$links->fetchAll(),'events'=>$events->fetchAll()],$visibility,'organization',$organization,$o['beacon_domain_id']?:null);$this->database->pdo()->prepare('UPDATE beacon_pages SET owner_type="organization",organization_id=:organization WHERE id=:id')->execute(['organization'=>$organization,'id'=>$id]);$this->activity($organization,$actor,'beacon.organization_page_published','beacon_page',$id,['visibility'=>$visibility]);return $id;
    }

    public function proposeQuest(string $actor,string $organization,string $recipient,string $title,string $description):void
    {
        $this->require($actor,$organization,'create_content');$title=trim($title);if($title===''||mb_strlen($title)>180)throw new RuntimeException('Give the Quest proposal a clear title.');$s=$this->database->pdo()->prepare('SELECT 1 FROM organization_memberships WHERE organization_id=:organization AND account_id=:recipient AND status="active"');$s->execute(['organization'=>$organization,'recipient'=>$recipient]);if(!$s->fetchColumn())throw new RuntimeException('Choose an active Organization member.');$id=self::uuid();$this->database->pdo()->prepare('INSERT INTO organization_quest_proposals (id,organization_id,proposed_by_account_id,recipient_account_id,title,description,status,created_at,updated_at) VALUES (:id,:organization,:actor,:recipient,:title,:description,"proposed",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$id,'organization'=>$organization,'actor'=>$actor,'recipient'=>$recipient,'title'=>$title,'description'=>trim($description)]);$this->activity($organization,$actor,'quest.proposed','quest_proposal',$id,['recipient_account_id'=>$recipient]);
    }

    public function respondToQuest(string $account,string $proposal,string $response):?string
    {
        if(!in_array($response,['accepted','declined'],true))throw new RuntimeException('Choose whether to accept or decline.');$s=$this->database->pdo()->prepare('SELECT * FROM organization_quest_proposals WHERE id=:id AND recipient_account_id=:account AND status="proposed"');$s->execute(['id'=>$proposal,'account'=>$account]);$p=$s->fetch();if(!$p)throw new RuntimeException('That Quest proposal is unavailable.');$quest=null;if($response==='accepted')$quest=(new QuestService($this->database))->create($account,(string)$p['title'],(string)$p['description'],['origin_type'=>'personal','origin_reference'=>$proposal]);$this->database->pdo()->prepare('UPDATE organization_quest_proposals SET status=:status,quest_id=:quest,responded_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['status'=>$response,'quest'=>$quest,'id'=>$proposal]);$this->activity((string)$p['organization_id'],$account,'quest.'.$response,'quest_proposal',$proposal,['quest_id'=>$quest]);return $quest;
    }

    public function operations(string $account,string $organization):array
    {
        $this->require($account,$organization,'view');$pdo=$this->database->pdo();$teams=$pdo->prepare('SELECT t.*,COUNT(tm.id) member_count FROM organization_teams t LEFT JOIN organization_team_memberships tm ON tm.team_id=t.id WHERE t.organization_id=:id AND t.status="active" GROUP BY t.id ORDER BY t.name');$teams->execute(['id'=>$organization]);$proposals=$pdo->prepare('SELECT p.*,a.display_name recipient_name FROM organization_quest_proposals p JOIN platform_accounts a ON a.id=p.recipient_account_id WHERE p.organization_id=:id ORDER BY p.created_at DESC LIMIT 20');$proposals->execute(['id'=>$organization]);$mine=$pdo->prepare('SELECT p.*,o.name organization_name FROM organization_quest_proposals p JOIN organizations o ON o.id=p.organization_id WHERE p.recipient_account_id=:account AND p.status="proposed" ORDER BY p.created_at DESC');$mine->execute(['account'=>$account]);$domains=$pdo->prepare('SELECT id,hostname,verification_status FROM beacon_domains WHERE verification_status="verified" AND (owner_type="platform" OR organization_id=:id) ORDER BY is_default DESC,hostname');$domains->execute(['id'=>$organization]);$recoveries=$pdo->prepare('SELECT * FROM organization_recovery_records WHERE organization_id=:id ORDER BY created_at DESC LIMIT 20');$recoveries->execute(['id'=>$organization]);return ['teams'=>$teams->fetchAll(),'proposals'=>$proposals->fetchAll(),'mine'=>$mine->fetchAll(),'domains'=>$domains->fetchAll(),'recoveries'=>$recoveries->fetchAll()];
    }

    private function require(string $account,string $organization,string $capability):void{if(!(new OrganizationService($this->database))->can($account,$organization,$capability))throw new RuntimeException('You do not have that Organization capability.');}
    private function activity(string $organization,string $actor,string $action,string $type,?string $subject,array $context):void{$this->database->pdo()->prepare('INSERT INTO organization_activity (id,organization_id,actor_account_id,action,subject_type,subject_id,context_json,created_at) VALUES (:id,:organization,:actor,:action,:type,:subject,:context,UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'organization'=>$organization,'actor'=>$actor,'action'=>$action,'type'=>$type,'subject'=>$subject,'context'=>json_encode($context,JSON_THROW_ON_ERROR)]);}
    private static function token():string{return rtrim(strtr(base64_encode(random_bytes(32)),'+/','-_'),'=');}
    private static function e(string $v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
    private static function uuid():string{$d=random_bytes(16);$d[6]=chr((ord($d[6])&0x0f)|0x40);$d[8]=chr((ord($d[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4));}
}
