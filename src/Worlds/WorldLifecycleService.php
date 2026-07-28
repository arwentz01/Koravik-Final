<?php

declare(strict_types=1);

namespace Koravik\Worlds;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class WorldLifecycleService
{
    public function __construct(private readonly Database $database) {}

    public function installed(string $accountId): array
    {
        $q=$this->database->pdo()->prepare('SELECT wi.id,wi.world_key,wi.status,wi.installed_version,wi.available_version,wi.installed_at,wi.last_played_at,wi.state_retained,wi.lifecycle_revision,wc.name,wc.tagline,wc.package_version,np.current_chapter,np.current_scene,(SELECT COUNT(*) FROM world_fact_permissions p WHERE p.installation_id=wi.id AND p.granted=1) permission_count FROM world_installations wi JOIN world_catalog wc ON wc.world_key=wi.world_key LEFT JOIN world_narrative_progress np ON np.installation_id=wi.id WHERE wi.account_id=:account_id ORDER BY (wi.status="active") DESC,wc.name');
        $q->execute(['account_id'=>$accountId]);return $q->fetchAll();
    }

    public function detail(string $accountId,string $worldKey): array
    {
        $q=$this->database->pdo()->prepare('SELECT wi.*,wc.name,wc.tagline,wc.package_version,np.current_chapter,np.current_scene,(SELECT COUNT(*) FROM world_fact_permissions p WHERE p.installation_id=wi.id AND p.granted=1) permission_count FROM world_installations wi JOIN world_catalog wc ON wc.world_key=wi.world_key LEFT JOIN world_narrative_progress np ON np.installation_id=wi.id WHERE wi.account_id=:account_id AND wi.world_key=:world_key LIMIT 1');
        $q->execute(['account_id'=>$accountId,'world_key'=>$worldKey]);$row=$q->fetch();if(!$row) throw new RuntimeException('That installed World is unavailable.');return $row;
    }

    public function activate(string $accountId,string $worldKey): void
    {
        $this->database->transaction(function(PDO $pdo) use($accountId,$worldKey):void{
            $target=$this->lock($pdo,$accountId,$worldKey);if($target['status']==='active') return;
            $pdo->prepare('UPDATE world_installations SET status="suspended",lifecycle_revision=lifecycle_revision+1 WHERE account_id=:account_id AND status="active"')->execute(['account_id'=>$accountId]);
            $pdo->prepare('UPDATE world_installations SET status="active",state_retained=1,last_played_at=UTC_TIMESTAMP(),lifecycle_revision=lifecycle_revision+1 WHERE id=:id')->execute(['id'=>$target['id']]);
            $this->history($pdo,$target,'activate','active','Activated this World while preserving every other World’s independent state.');
        });
    }

    public function suspend(string $accountId,string $worldKey): void{$this->status($accountId,$worldKey,'suspended','suspend','Suspended narrative processing while preserving World State.');}
    public function retainUninstall(string $accountId,string $worldKey): void{$this->status($accountId,$worldKey,'uninstalled','uninstall-retain','Uninstalled active access while retaining recoverable World State.');}

    public function updatePackage(string $accountId,string $worldKey): void
    {
        $this->database->transaction(function(PDO $pdo) use($accountId,$worldKey):void{
            $row=$this->lock($pdo,$accountId,$worldKey);if($row['installed_version']===$row['available_version']) return;
            $pdo->prepare('UPDATE world_installations SET installed_version=available_version,lifecycle_revision=lifecycle_revision+1 WHERE id=:id')->execute(['id'=>$row['id']]);
            $this->history($pdo,$row,'package-update',(string)$row['status'],'Updated the World package while preserving account-specific progress.');
        });
    }

    public function restart(string $accountId,string $worldKey,string $confirmation): void
    {
        if($confirmation!=='RESTART WORLD') throw new RuntimeException('Type RESTART WORLD to confirm.');
        $this->database->transaction(function(PDO $pdo) use($accountId,$worldKey):void{
            $row=$this->lock($pdo,$accountId,$worldKey);$id=(string)$row['id'];
            foreach(['world_story_history','world_keepsakes','world_objectives','world_choice_history','world_relationship_history','world_reactions','world_event_receipts','world_state'] as $table)$pdo->prepare("DELETE FROM {$table} WHERE installation_id=:id")->execute(['id'=>$id]);
            $pdo->prepare('DELETE FROM world_relationships WHERE installation_id=:id')->execute(['id'=>$id]);
            $pdo->prepare('DELETE FROM world_narrative_progress WHERE installation_id=:id')->execute(['id'=>$id]);
            $pdo->prepare('INSERT INTO world_relationships (installation_id,npc_key,trust_score,relationship_stage,updated_at) VALUES (:id,"caretaker",0,"new",UTC_TIMESTAMP())')->execute(['id'=>$id]);
            $pdo->prepare('INSERT INTO world_narrative_progress (installation_id,current_arc,current_chapter,current_scene,updated_at) VALUES (:id,"coming-home","the-first-light","caretaker-welcome",UTC_TIMESTAMP())')->execute(['id'=>$id]);
            $pdo->prepare('UPDATE world_installations SET status="active",state_retained=1,last_played_at=UTC_TIMESTAMP(),lifecycle_revision=lifecycle_revision+1 WHERE id=:id')->execute(['id'=>$id]);
            $this->history($pdo,$row,'restart','active','Reset only this World’s account-specific progress. Account identity, Quests, Chronicle, Companion memory, other Worlds, and audit evidence were untouched.');
        });
    }

    public function deleteState(string $accountId,string $worldKey,string $confirmation): void
    {
        if($confirmation!=='DELETE WORLD STATE') throw new RuntimeException('Type DELETE WORLD STATE to confirm.');
        $this->database->transaction(function(PDO $pdo) use($accountId,$worldKey):void{
            $row=$this->lock($pdo,$accountId,$worldKey);$id=(string)$row['id'];
            foreach(['world_story_history','world_keepsakes','world_objectives','world_choice_history','world_relationship_history','world_reactions','world_event_receipts','world_state'] as $table)$pdo->prepare("DELETE FROM {$table} WHERE installation_id=:id")->execute(['id'=>$id]);
            $pdo->prepare('DELETE FROM world_relationships WHERE installation_id=:id')->execute(['id'=>$id]);
            $pdo->prepare('DELETE FROM world_narrative_progress WHERE installation_id=:id')->execute(['id'=>$id]);
            $pdo->prepare('UPDATE world_installations SET status="uninstalled",state_retained=0,last_played_at=NULL,lifecycle_revision=lifecycle_revision+1 WHERE id=:id')->execute(['id'=>$id]);
            $this->history($pdo,$row,'uninstall-delete','uninstalled','Deleted eligible account-specific World State while retaining the shared package definition and lifecycle evidence.');
        });
    }

    private function status(string $accountId,string $worldKey,string $status,string $action,string $summary):void
    {
        $this->database->transaction(function(PDO $pdo) use($accountId,$worldKey,$status,$action,$summary):void{$row=$this->lock($pdo,$accountId,$worldKey);if($row['status']===$status) return;$pdo->prepare('UPDATE world_installations SET status=:status,state_retained=1,lifecycle_revision=lifecycle_revision+1 WHERE id=:id')->execute(['status'=>$status,'id'=>$row['id']]);$this->history($pdo,$row,$action,$status,$summary);});
    }
    private function lock(PDO $pdo,string $accountId,string $worldKey):array{$q=$pdo->prepare('SELECT * FROM world_installations WHERE account_id=:account_id AND world_key=:world_key LIMIT 1 FOR UPDATE');$q->execute(['account_id'=>$accountId,'world_key'=>$worldKey]);$row=$q->fetch();if(!$row) throw new RuntimeException('That installed World is unavailable.');return $row;}
    private function history(PDO $pdo,array $row,string $action,string $result,string $summary):void{$revision=(int)$row['lifecycle_revision']+1;$pdo->prepare('INSERT INTO world_lifecycle_history (id,installation_id,action_key,prior_status,resulting_status,consequence_summary,revision,occurred_at) VALUES (:id,:installation_id,:action,:prior_status,:result,:summary,:revision,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE action_key=VALUES(action_key)')->execute(['id'=>self::uuid(),'installation_id'=>$row['id'],'action'=>$action,'prior_status'=>$row['status'],'result'=>$result,'summary'=>$summary,'revision'=>$revision]);}
    private static function uuid():string{$b=random_bytes(16);$b[6]=chr((ord($b[6])&0x0f)|0x40);$b[8]=chr((ord($b[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($b),4));}
}