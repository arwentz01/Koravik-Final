<?php

declare(strict_types=1);

namespace Koravik\Platform\Notifications;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class NotificationService
{
    public const CATEGORIES = [
        'world.reactions' => 'World reactions',
        'platform.return' => 'Welcome-back summaries',
        'household.coordination' => 'Household coordination',
        'gather.activity' => 'Gather event activity',
        'gather.followup' => 'Gather follow-up',
        'beacon.campaigns' => 'Beacon campaigns',
        'health.private' => 'Health reminders',
    ];

    public function __construct(private readonly Database $database) {}

    public function ensurePreferences(string $accountId): void
    {
        $statement = $this->database->pdo()->prepare('INSERT INTO notification_preferences (account_id,category,enabled,updated_at) VALUES (:account_id,:category,1,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE account_id=VALUES(account_id)');
        foreach (array_keys(self::CATEGORIES) as $category) $statement->execute(['account_id'=>$accountId,'category'=>$category]);
    }

    public function synchronize(string $accountId): void
    {
        $this->ensurePreferences($accountId);
        $reactions=$this->database->pdo()->prepare('SELECT wr.source_event_id,wr.title,wr.message FROM world_reactions wr JOIN world_installations wi ON wi.id=wr.installation_id WHERE wi.account_id=:account_id AND NOT EXISTS (SELECT 1 FROM notifications n WHERE n.account_id=:notification_account AND n.source_event_id=wr.source_event_id AND n.category="world.reactions") ORDER BY wr.created_at ASC LIMIT 50');
        $reactions->execute(['account_id'=>$accountId,'notification_account'=>$accountId]);
        foreach($reactions->fetchAll() as $row) $this->create($accountId,'Epic Ordinary','world.reactions',(string)$row['title'],(string)$row['message'],'/world/reaction','Sent because Epic Ordinary was active and permitted to interpret a completed Quest occurrence.',(string)$row['source_event_id']);

        $returns=$this->database->pdo()->prepare('SELECT po.id,po.payload_json FROM platform_outbox po WHERE po.account_id=:account_id AND po.event_name="Platform.PlayerReturned" AND po.status="delivered" AND NOT EXISTS (SELECT 1 FROM notifications n WHERE n.account_id=:notification_account AND n.source_event_id=po.id AND n.category="platform.return") ORDER BY po.occurred_at ASC LIMIT 20');
        $returns->execute(['account_id'=>$accountId,'notification_account'=>$accountId]);
        foreach($returns->fetchAll() as $row) {
            $payload=json_decode((string)$row['payload_json'],true,512,JSON_THROW_ON_ERROR);
            $days=max(7,(int)($payload['days_away']??7));
            $this->create($accountId,'Koravik','platform.return','Your welcome-back review is ready','A calm summary is available after about '.$days.' days away.','/return','Sent because Koravik detected a meaningful absence of at least seven days.',(string)$row['id']);
        }

        $this->synchronizeGatherActivity($accountId);

        $followups=$this->database->pdo()->prepare('SELECT f.id,f.title,e.title event_title FROM gather_event_followups f JOIN gather_events e ON e.id=f.event_id WHERE f.author_account_id=:account_id AND f.status="draft" AND NOT EXISTS (SELECT 1 FROM notifications n WHERE n.account_id=:notification_account AND n.source_event_id=f.id AND n.category="gather.followup") ORDER BY f.created_at ASC LIMIT 20');
        $followups->execute(['account_id'=>$accountId,'notification_account'=>$accountId]);
        foreach($followups->fetchAll() as $row) $this->create($accountId,'Gather','gather.followup','Follow-up draft waiting: '.(string)$row['title'],'A post-event follow-up is drafted for '.(string)$row['event_title'].'.','/gather/outcomes/'.(string)$row['id'].'/review','Sent because a Gather-owned follow-up is waiting for explicit review before anything crosses boundaries.',(string)$row['id']);

        $campaigns=$this->database->pdo()->prepare('SELECT id,title,status FROM beacon_campaigns WHERE account_id=:account_id AND status IN ("draft","paused") AND NOT EXISTS (SELECT 1 FROM notifications n WHERE n.account_id=:notification_account AND n.source_event_id=beacon_campaigns.id AND n.category="beacon.campaigns") ORDER BY updated_at ASC LIMIT 20');
        $campaigns->execute(['account_id'=>$accountId,'notification_account'=>$accountId]);
        foreach($campaigns->fetchAll() as $row) $this->create($accountId,'Beacon','beacon.campaigns','Campaign needs review: '.(string)$row['title'],'This Beacon campaign is '.(string)$row['status'].' and can be opened from Beacon.','/beacon/campaigns/'.(string)$row['id'],'Sent because Beacon owns a public-facing campaign that is not active.',(string)$row['id']);
    }

    private function synchronizeGatherActivity(string $accountId): void
    {
        if(!$this->enabled($accountId,'gather.activity')) return;
        $pdo=$this->database->pdo();
        $access='((e.owner_type="account" AND e.account_id=:account_owner) OR (e.owner_type="organization" AND EXISTS (SELECT 1 FROM organization_memberships om WHERE om.organization_id=e.organization_id AND om.account_id=:org_account AND om.status="active" AND om.role IN ("owner","admin","creator"))) OR (e.owner_type="household" AND EXISTS (SELECT 1 FROM household_memberships hm WHERE hm.household_id=e.household_id AND hm.account_id=:household_account AND hm.status="active" AND hm.role IN ("owner","admin","member"))))';
        $params=['account_owner'=>$accountId,'org_account'=>$accountId,'household_account'=>$accountId];

        $rsvps=$pdo->prepare('SELECT r.id,r.guest_name,r.response,r.party_size,r.status,r.created_at,r.updated_at,e.id event_id,e.title event_title,e.owner_type,o.name organization_name,h.name household_name FROM gather_rsvps r JOIN gather_events e ON e.id=r.event_id LEFT JOIN organizations o ON o.id=e.organization_id LEFT JOIN households h ON h.id=e.household_id WHERE '.$access.' AND r.updated_at>=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 90 DAY) ORDER BY r.updated_at ASC LIMIT 100');
        $rsvps->execute($params);
        foreach($rsvps->fetchAll() as $row) {
            $isNew=((string)$row['created_at']===(string)$row['updated_at']);
            $context=$this->gatherContext($row);
            $party=max(1,(int)$row['party_size']);
            $name=trim((string)$row['guest_name'])?:'A guest';
            $response=strtolower((string)$row['response']);
            $status=strtolower((string)($row['status']??''));
            if($status==='cancelled' || $response==='no') {
                $title='RSVP cancelled · '.$context;
                $body=$name.' is no longer attending '.$row['event_title'].'.';
            } elseif($isNew) {
                $title='New RSVP · '.$context;
                $body=$name.' RSVP’d '.ucfirst($response?:'yes').($party>1?' for '.$party.' people':'').' to '.$row['event_title'].'.';
            } else {
                $title='RSVP updated · '.$context;
                $body=$name.' changed their RSVP to '.ucfirst($response?:$status?:'updated').($party>1?' for '.$party.' people':'').' for '.$row['event_title'].'.';
            }
            $source=$this->activitySourceId('rsvp',(string)$row['id'],(string)$row['updated_at']);
            $this->create($accountId,'Gather','gather.activity',$title,$body,'/gather/events/'.(string)$row['event_id'].'/command','Shown because you can manage this event. Koravik includes activity from every organization and household you manage, not only the currently selected context.',$source);
        }

        $commitments=$pdo->prepare('SELECT c.id,c.participant_name,c.participant_email,c.quantity,c.status,c.created_at,c.updated_at,s.title slot_title,e.id event_id,e.title event_title,e.owner_type,o.name organization_name,h.name household_name FROM gather_signup_commitments c JOIN gather_signup_slots s ON s.id=c.slot_id JOIN gather_events e ON e.id=s.event_id LEFT JOIN organizations o ON o.id=e.organization_id LEFT JOIN households h ON h.id=e.household_id WHERE '.$access.' AND c.updated_at>=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 90 DAY) ORDER BY c.updated_at ASC LIMIT 100');
        $commitments->execute($params);
        foreach($commitments->fetchAll() as $row) {
            $isNew=((string)$row['created_at']===(string)$row['updated_at']);
            $context=$this->gatherContext($row);
            $name=trim((string)$row['participant_name']);
            if($name==='') $name=trim((string)$row['participant_email'])?:'A participant';
            $quantity=max(1,(int)$row['quantity']);
            $status=strtolower((string)$row['status']);
            if($status==='cancelled') {
                $title='Signup cancelled · '.$context;
                $body=$name.' released '.$row['slot_title'].' for '.$row['event_title'].'.';
            } elseif($status==='waitlist') {
                $title=($isNew?'Signup waitlist':'Signup updated').' · '.$context;
                $body=$name.' is waitlisted for '.$row['slot_title'].' at '.$row['event_title'].'.';
            } elseif($isNew) {
                $title='New signup · '.$context;
                $body=$name.' claimed '.$row['slot_title'].($quantity>1?' × '.$quantity:'').' for '.$row['event_title'].'.';
            } else {
                $title='Signup updated · '.$context;
                $body=$name.' updated '.$row['slot_title'].' for '.$row['event_title'].'.';
            }
            $source=$this->activitySourceId('signup',(string)$row['id'],(string)$row['updated_at']);
            $this->create($accountId,'Gather','gather.activity',$title,$body,'/gather/events/'.(string)$row['event_id'].'/command','Shown because you can manage this event. Koravik includes activity from every organization and household you manage, not only the currently selected context.',$source);
        }
    }

    private function gatherContext(array $row): string
    {
        if((string)($row['owner_type']??'account')==='organization') return trim((string)($row['organization_name']??''))?:'Organization event';
        if((string)($row['owner_type']??'account')==='household') return trim((string)($row['household_name']??''))?:'Household event';
        return 'Personal event';
    }

    private function activitySourceId(string $type,string $id,string $updatedAt): string
    {
        $hex=md5($type.'|'.$id.'|'.$updatedAt);
        return substr($hex,0,8).'-'.substr($hex,8,4).'-4'.substr($hex,13,3).'-8'.substr($hex,17,3).'-'.substr($hex,20,12);
    }

    public function enabled(string $accountId,string $category): bool
    {
        $this->ensurePreferences($accountId);
        $statement=$this->database->pdo()->prepare('SELECT enabled FROM notification_preferences WHERE account_id=:account_id AND category=:category');
        $statement->execute(['account_id'=>$accountId,'category'=>$category]);
        return (bool)$statement->fetchColumn();
    }

    public function create(string $accountId,string $sourceModule,string $category,string $title,string $body,string $targetUrl,string $reason,?string $sourceEventId): void
    {
        if(!$this->enabled($accountId,$category)) return;
        $this->database->pdo()->prepare('INSERT IGNORE INTO notifications (id,account_id,source_module,category,title,body,target_url,reason,source_event_id,created_at) VALUES (:id,:account_id,:source_module,:category,:title,:body,:target_url,:reason,:source_event_id,UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'source_module'=>$sourceModule,'category'=>$category,'title'=>$title,'body'=>$body,'target_url'=>$targetUrl,'reason'=>$reason,'source_event_id'=>$sourceEventId]);
    }

    public function list(string $accountId): array
    {
        $this->synchronize($accountId);
        $statement=$this->database->pdo()->prepare('SELECT id,source_module,category,title,body,target_url,reason,read_at,created_at FROM notifications WHERE account_id=:account_id AND dismissed_at IS NULL ORDER BY read_at IS NULL DESC, created_at DESC LIMIT 100');
        $statement->execute(['account_id'=>$accountId]);
        return $statement->fetchAll();
    }

    public function unreadCount(string $accountId): int
    {
        $this->synchronize($accountId);
        $statement=$this->database->pdo()->prepare('SELECT COUNT(*) FROM notifications WHERE account_id=:account_id AND read_at IS NULL AND dismissed_at IS NULL');
        $statement->execute(['account_id'=>$accountId]);
        return (int)$statement->fetchColumn();
    }

    public function changeState(string $accountId,string $notificationId,string $action): void
    {
        $sql=match($action){'read'=>'UPDATE notifications SET read_at=COALESCE(read_at,UTC_TIMESTAMP()) WHERE id=:id AND account_id=:account_id AND dismissed_at IS NULL','unread'=>'UPDATE notifications SET read_at=NULL WHERE id=:id AND account_id=:account_id AND dismissed_at IS NULL','dismiss'=>'UPDATE notifications SET dismissed_at=UTC_TIMESTAMP() WHERE id=:id AND account_id=:account_id',default=>throw new RuntimeException('Unknown notification action.')};
        $statement=$this->database->pdo()->prepare($sql);$statement->execute(['id'=>$notificationId,'account_id'=>$accountId]);
        if($statement->rowCount()!==1) throw new RuntimeException('That notification is no longer available.');
    }

    public function markAllRead(string $accountId): void
    {
        $this->database->pdo()->prepare('UPDATE notifications SET read_at=COALESCE(read_at,UTC_TIMESTAMP()) WHERE account_id=:account_id AND dismissed_at IS NULL')->execute(['account_id'=>$accountId]);
    }

    public function preferences(string $accountId): array
    {
        $this->ensurePreferences($accountId);
        $statement=$this->database->pdo()->prepare('SELECT category,enabled FROM notification_preferences WHERE account_id=:account_id');$statement->execute(['account_id'=>$accountId]);$values=[];
        foreach($statement->fetchAll() as $row) $values[(string)$row['category']]=(bool)$row['enabled'];
        return $values;
    }

    public function savePreferences(string $accountId,array $enabledCategories): void
    {
        $this->database->transaction(function(PDO $pdo) use($accountId,$enabledCategories): void {$statement=$pdo->prepare('INSERT INTO notification_preferences (account_id,category,enabled,updated_at) VALUES (:account_id,:category,:enabled,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE enabled=VALUES(enabled),updated_at=VALUES(updated_at)');foreach(array_keys(self::CATEGORIES) as $category) $statement->execute(['account_id'=>$accountId,'category'=>$category,'enabled'=>in_array($category,$enabledCategories,true)?1:0]);});
    }

    private static function uuid(): string
    {
        $bytes=random_bytes(16);$bytes[6]=chr((ord($bytes[6])&0x0f)|0x40);$bytes[8]=chr((ord($bytes[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($bytes),4));
    }
}
