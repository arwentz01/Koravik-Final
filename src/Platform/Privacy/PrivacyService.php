<?php

declare(strict_types=1);

namespace Koravik\Platform\Privacy;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class PrivacyService
{
    private const FACTS = [
        'quest.completed' => ['label'=>'Quest occurrence completed','purpose'=>'Allows Epic Ordinary to notice a completed occurrence and update its fictional relationship and story state.','source'=>'Quests'],
        'player.returned' => ['label'=>'Player returned after an absence','purpose'=>'Allows Epic Ordinary to acknowledge a return without receiving Quest titles, notes, or stale-item details.','source'=>'Platform'],
        'health.wellbeing_band' => ['label'=>'Health derived energy band','purpose'=>'Allows a recipient to receive only low, steady, or full plus a date when Health sharing is explicitly enabled. Feeling words and notes stay private.','source'=>'Health'],
    ];

    public function __construct(private readonly Database $database) {}

    public function grants(string $accountId): array
    {
        $statement=$this->database->pdo()->prepare('SELECT wi.id AS installation_id,wi.status AS installation_status,wc.name AS recipient,p.fact_key,p.granted,p.explanation,p.granted_at,p.revoked_at,(SELECT MAX(po.occurred_at) FROM world_reactions wr JOIN platform_outbox po ON po.id=wr.source_event_id WHERE wr.installation_id=wi.id AND ((p.fact_key="quest.completed" AND po.event_name="Quests.QuestCompleted") OR (p.fact_key="player.returned" AND po.event_name="Platform.PlayerReturned"))) AS last_used_at FROM world_installations wi JOIN world_catalog wc ON wc.world_key=wi.world_key JOIN world_fact_permissions p ON p.installation_id=wi.id WHERE wi.account_id=:account_id ORDER BY wc.name,p.fact_key');
        $statement->execute(['account_id'=>$accountId]);
        $rows=[];
        foreach($statement->fetchAll() as $row){$meta=self::FACTS[(string)$row['fact_key']]??['label'=>(string)$row['fact_key'],'purpose'=>(string)$row['explanation'],'source'=>'Platform'];$row['label']=$meta['label'];$row['purpose']=$meta['purpose'];$row['source']=$meta['source'];$rows[]=$row;}
        $companion=$this->database->pdo()->prepare('SELECT context_key,allowed,updated_at FROM companion_context_permissions WHERE account_id=:account_id ORDER BY context_key');
        $companion->execute(['account_id'=>$accountId]);
        foreach($companion->fetchAll() as $row)$rows[]=[
            'installation_id'=>$accountId,
            'installation_status'=>'active',
            'recipient'=>'Companion',
            'fact_key'=>(string)$row['context_key'],
            'granted'=>(bool)$row['allowed'],
            'explanation'=>'Companion may use this approved context only inside proposal and help workflows.',
            'granted_at'=>(string)$row['updated_at'],
            'revoked_at'=>(bool)$row['allowed']?null:(string)$row['updated_at'],
            'last_used_at'=>'Controlled by Companion approval history',
            'label'=>'Companion context: '.ucwords(str_replace(['_','.'],' ',(string)$row['context_key'])),
            'purpose'=>'Controls whether Companion may reference this context while preparing help or proposals. Consequential actions still require approval.',
            'source'=>'Companion Memory / Consent',
            'readonly'=>true,
        ];
        $health=$this->database->pdo()->prepare('SELECT MAX(updated_at) last_changed,COUNT(*) shared_count FROM health_wellbeing_checkins WHERE account_id=:account_id AND share_derived_fact=1');
        $health->execute(['account_id'=>$accountId]);$healthRow=$health->fetch();
        $rows[]=[
            'installation_id'=>$accountId,
            'installation_status'=>'active',
            'recipient'=>'Approved event consumers',
            'fact_key'=>'health.wellbeing_band',
            'granted'=>((int)($healthRow['shared_count']??0)>0),
            'explanation'=>self::FACTS['health.wellbeing_band']['purpose'],
            'granted_at'=>(string)($healthRow['last_changed']??''),
            'revoked_at'=>null,
            'last_used_at'=>(string)($healthRow['last_changed']??'Never'),
            'label'=>self::FACTS['health.wellbeing_band']['label'],
            'purpose'=>self::FACTS['health.wellbeing_band']['purpose'],
            'source'=>'Health',
            'readonly'=>true,
        ];
        return $rows;
    }

    public function setGrant(string $accountId,string $installationId,string $factKey,bool $granted): void
    {
        if(!isset(self::FACTS[$factKey])) throw new RuntimeException('That permission cannot be changed here.');
        $this->database->transaction(function(PDO $pdo) use($accountId,$installationId,$factKey,$granted): void {
            $check=$pdo->prepare('SELECT id FROM world_installations WHERE id=:id AND account_id=:account_id FOR UPDATE');
            $check->execute(['id'=>$installationId,'account_id'=>$accountId]);
            if(!$check->fetchColumn()) throw new RuntimeException('That installation is unavailable.');
            $now=gmdate('Y-m-d H:i:s');
            $update=$pdo->prepare('UPDATE world_fact_permissions SET granted=:granted,granted_at=:granted_at,revoked_at=:revoked_at,updated_at=:updated_at WHERE installation_id=:installation_id AND fact_key=:fact_key');
            $update->execute(['granted'=>$granted?1:0,'granted_at'=>$granted?$now:null,'revoked_at'=>$granted?null:$now,'updated_at'=>$now,'installation_id'=>$installationId,'fact_key'=>$factKey]);
            if($update->rowCount()!==1) throw new RuntimeException('That permission is unavailable.');
            $pdo->prepare('INSERT INTO audit_log (id,account_id,action,subject_type,subject_id,occurred_at) VALUES (:id,:account_id,:action,:subject_type,:subject_id,:occurred_at)')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'action'=>$granted?'consent.granted':'consent.revoked','subject_type'=>'consent.'.$factKey,'subject_id'=>$installationId,'occurred_at'=>$now]);
        });
    }

    public function audit(string $accountId): array
    {
        $statement=$this->database->pdo()->prepare('SELECT id,action,subject_type,subject_id,occurred_at FROM audit_log WHERE account_id=:account_id ORDER BY occurred_at DESC LIMIT 150');
        $statement->execute(['account_id'=>$accountId]);
        $rows=[];
        foreach($statement->fetchAll() as $row){$row['summary']=$this->summary((string)$row['action']);$row['module']=$this->module((string)$row['action'],(string)$row['subject_type']);$rows[]=$row;}
        return $rows;
    }

    private function summary(string $action): string
    {
        return match($action){
            'consent.granted','world.permission.granted'=>'Permission was granted for future fact delivery.',
            'consent.revoked','world.permission.revoked'=>'Permission was revoked for future fact delivery.',
            'world.installed'=>'A World was installed.','world.active'=>'A World was resumed.','world.suspended'=>'A World was suspended.','world.uninstalled'=>'A World was uninstalled while retaining its state.',
            'quest.occurrence.completed'=>'A Quest occurrence was completed.','quest.occurrence.completion_reversed'=>'A Quest completion was reversed.','platform.player.returned'=>'A meaningful return after absence was detected.',
            'health.checkin.created'=>'A private Health observation was created.','health.checkin.corrected'=>'A private Health observation was corrected.','health.checkin.deleted'=>'A private Health observation was deleted.',
            'beacon.campaign.created'=>'A Beacon campaign was created.','beacon.campaign.updated'=>'A Beacon campaign was updated.','gather.followup.created'=>'A Gather follow-up draft was created.',
            default=>ucfirst(str_replace(['.','_'],' ',$action)).'.',
        };
    }

    private function module(string $action,string $subjectType): string
    {
        if(str_starts_with($action,'world.')||str_starts_with($action,'consent.')) return 'Worlds / Consent';
        if(str_starts_with($action,'quest')) return 'Quests';
        if(str_starts_with($action,'platform')) return 'Platform';
        if(str_starts_with($action,'health')) return 'Health';
        if(str_starts_with($action,'beacon')) return 'Beacon';
        if(str_starts_with($action,'gather')) return 'Gather';
        return ucfirst($subjectType?:'Platform');
    }

    private static function uuid(): string
    {
        $bytes=random_bytes(16);$bytes[6]=chr((ord($bytes[6])&0x0f)|0x40);$bytes[8]=chr((ord($bytes[8])&0x3f)|0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($bytes),4));
    }
}
