<?php

declare(strict_types=1);

namespace Koravik\Worlds\EpicOrdinary;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class ChapterTwoService
{
    public function __construct(private readonly Database $database) {}

    public function home(string $accountId): array
    {
        $pdo=$this->database->pdo();
        $statement=$pdo->prepare('SELECT wi.id AS installation_id,wi.status,np.current_arc,np.current_chapter,np.current_scene,wr.trust_score,wr.relationship_stage,
            (SELECT choice_key FROM world_choice_history WHERE installation_id=wi.id AND scene_key="caretaker-welcome" LIMIT 1) support_choice,
            (SELECT choice_label FROM world_choice_history WHERE installation_id=wi.id AND scene_key="caretaker-welcome" LIMIT 1) support_choice_label,
            (SELECT granted FROM world_fact_permissions WHERE installation_id=wi.id AND fact_key="quest.completed" LIMIT 1) quest_fact_granted
            FROM world_installations wi
            JOIN world_narrative_progress np ON np.installation_id=wi.id
            LEFT JOIN world_relationships wr ON wr.installation_id=wi.id AND wr.npc_key="caretaker"
            WHERE wi.account_id=:account_id AND wi.world_key="epic-ordinary" AND wi.status="active" LIMIT 1');
        $statement->execute(['account_id'=>$accountId]);
        $state=$statement->fetch();
        if(!$state) throw new RuntimeException('Epic Ordinary is not active for this account.');

        $objective=$pdo->prepare('SELECT objective_key,title,description,status,completed_at FROM world_objectives WHERE installation_id=:installation_id ORDER BY created_at DESC LIMIT 1');
        $objective->execute(['installation_id'=>$state['installation_id']]);
        $state['objective']=$objective->fetch()?:null;

        $keepsakes=$pdo->prepare('SELECT keepsake_key,name,description,acquired_at FROM world_keepsakes WHERE installation_id=:installation_id ORDER BY acquired_at DESC');
        $keepsakes->execute(['installation_id'=>$state['installation_id']]);
        $state['keepsakes']=$keepsakes->fetchAll();

        $reaction=$pdo->prepare('SELECT title,message,explanation,created_at FROM world_reactions WHERE installation_id=:installation_id ORDER BY created_at DESC LIMIT 1');
        $reaction->execute(['installation_id'=>$state['installation_id']]);
        $state['recent_reaction']=$reaction->fetch()?:null;

        $history=$pdo->prepare('SELECT delta_value,reason_code,explanation,created_at FROM world_relationship_history WHERE installation_id=:installation_id AND npc_key="caretaker" ORDER BY created_at DESC LIMIT 5');
        $history->execute(['installation_id'=>$state['installation_id']]);
        $state['relationship_history']=$history->fetchAll();
        $choices=$pdo->prepare('SELECT id,scene_key,choice_key,choice_label,created_at FROM world_choice_history WHERE installation_id=:installation_id AND scene_key IN ("eastern-room-purpose","listening-wall-truth")');
        $choices->execute(['installation_id'=>$state['installation_id']]);
        foreach($choices->fetchAll() as $choice)$state[$choice['scene_key']==='eastern-room-purpose'?'refuge_choice':'listening_choice']=$choice;
        return $state;
    }

    public function begin(string $accountId): void
    {
        $this->database->transaction(function(PDO $pdo) use($accountId): void {
            $state=$this->lock($pdo,$accountId);
            if(!$state['support_choice']) throw new RuntimeException('Choose how the Caretaker should support you before beginning Chapter Two.');
            if((string)$state['current_chapter']==='the-eastern-room') return;
            $now=gmdate('Y-m-d H:i:s');
            $pdo->prepare('UPDATE world_narrative_progress SET current_arc="making-refuge",current_chapter="the-eastern-room",current_scene="doorway",updated_at=:updated_at WHERE installation_id=:installation_id')->execute(['updated_at'=>$now,'installation_id'=>$state['installation_id']]);
            $pdo->prepare('INSERT INTO world_objectives (id,installation_id,objective_key,title,description,status,created_at) VALUES (:id,:installation_id,"choose-refuge","Decide what kind of refuge this will become","The Caretaker has opened the eastern room. Choose what the restored space should offer.","active",:created_at) ON DUPLICATE KEY UPDATE status=IF(status="retired","active",status)')->execute(['id'=>self::uuid(),'installation_id'=>$state['installation_id'],'created_at'=>$now]);
            $this->relationshipMoment($pdo,(string)$state['installation_id'],0,'chapter.two.begin','The Caretaker opened the eastern room without treating the time before your return as a failure.',$now);
        });
    }

    public function chooseRefuge(string $accountId,string $choice): void
    {
        $choices=[
            'rest'=>['label'=>'A room for rest','keepsake'=>'linen-thread','name'=>'A Linen Thread','description'=>'A pale thread from the first curtain hung in the restored room.'],
            'making'=>['label'=>'A room for making','keepsake'=>'charcoal-mark','name'=>'A Charcoal Mark','description'=>'A dark mark from the first plan drawn on the eastern wall.'],
            'welcome'=>['label'=>'A room for welcome','keepsake'=>'small-key','name'=>'A Small Brass Key','description'=>'A key meant for a door that can be opened from either side.'],
        ];
        if(!isset($choices[$choice])) throw new RuntimeException('Choose what kind of refuge the eastern room should become.');
        $this->database->transaction(function(PDO $pdo) use($accountId,$choice,$choices): void {
            $state=$this->lock($pdo,$accountId);
            if((string)$state['current_chapter']!=='the-eastern-room') throw new RuntimeException('Chapter Two has not begun.');
            $existing=$pdo->prepare('SELECT id FROM world_choice_history WHERE installation_id=:installation_id AND scene_key="eastern-room-purpose" LIMIT 1');
            $existing->execute(['installation_id'=>$state['installation_id']]);
            if($existing->fetchColumn()) return;
            $now=gmdate('Y-m-d H:i:s');$selected=$choices[$choice];
            $pdo->prepare('INSERT INTO world_choice_history (id,installation_id,scene_key,choice_key,choice_label,created_at) VALUES (:id,:installation_id,"eastern-room-purpose",:choice_key,:choice_label,:created_at)')->execute(['id'=>self::uuid(),'installation_id'=>$state['installation_id'],'choice_key'=>$choice,'choice_label'=>$selected['label'],'created_at'=>$now]);
            $pdo->prepare('UPDATE world_objectives SET status="completed",completed_at=:completed_at WHERE installation_id=:installation_id AND objective_key="choose-refuge"')->execute(['completed_at'=>$now,'installation_id'=>$state['installation_id']]);
            $pdo->prepare('INSERT INTO world_keepsakes (id,installation_id,keepsake_key,name,description,source_scene,acquired_at) VALUES (:id,:installation_id,:keepsake_key,:name,:description,"eastern-room-purpose",:acquired_at)')->execute(['id'=>self::uuid(),'installation_id'=>$state['installation_id'],'keepsake_key'=>$selected['keepsake'],'name'=>$selected['name'],'description'=>$selected['description'],'acquired_at'=>$now]);
            $pdo->prepare('UPDATE world_narrative_progress SET current_scene="room-restored",updated_at=:updated_at WHERE installation_id=:installation_id')->execute(['updated_at'=>$now,'installation_id'=>$state['installation_id']]);
            $pdo->prepare('UPDATE world_relationships SET trust_score=trust_score+1,relationship_stage=CASE WHEN trust_score+1>=5 THEN "trusted" ELSE relationship_stage END,updated_at=:updated_at WHERE installation_id=:installation_id AND npc_key="caretaker"')->execute(['updated_at'=>$now,'installation_id'=>$state['installation_id']]);
            $this->relationshipMoment($pdo,(string)$state['installation_id'],1,'chapter.two.choice','You chose '.$selected['label'].'. The Caretaker treated that choice as direction, not a test.',$now);
        });
    }

    public function beginChapterThree(string $accountId): void
    {
        $this->database->transaction(function(PDO $pdo) use($accountId): void {
            $state=$this->lock($pdo,$accountId);
            $refuge=$pdo->prepare('SELECT id FROM world_choice_history WHERE installation_id=:installation_id AND scene_key="eastern-room-purpose" LIMIT 1');
            $refuge->execute(['installation_id'=>$state['installation_id']]);
            if(!$refuge->fetchColumn()) throw new RuntimeException('Choose the Eastern Room purpose before beginning Chapter Three.');
            if((string)$state['current_chapter']==='the-listening-wall') return;
            $now=gmdate('Y-m-d H:i:s');
            $pdo->prepare('UPDATE world_narrative_progress SET current_arc="what-the-house-keeps",current_chapter="the-listening-wall",current_scene="the-sound-behind-plaster",updated_at=:updated_at WHERE installation_id=:installation_id')->execute(['updated_at'=>$now,'installation_id'=>$state['installation_id']]);
            $pdo->prepare('INSERT INTO world_objectives (id,installation_id,objective_key,title,description,status,created_at) VALUES (:id,:installation_id,"choose-what-the-house-keeps","Choose what the house should keep","A quiet sound has returned behind the library wall. Decide what kind of truth deserves a place there.","active",:created_at) ON DUPLICATE KEY UPDATE status=IF(status="retired","active",status)')->execute(['id'=>self::uuid(),'installation_id'=>$state['installation_id'],'created_at'=>$now]);
            $this->relationshipMoment($pdo,(string)$state['installation_id'],0,'chapter.three.begin','The Caretaker invited you to listen, then waited for your interpretation instead of supplying one.',$now);
        });
    }

    public function chooseListeningTruth(string $accountId,string $choice): void
    {
        $choices=[
            'courage'=>['label'=>'Keep the courage it took to begin','keepsake'=>'copper-bell','name'=>'A Small Copper Bell','description'=>'A bell that sounds for beginnings, including the quiet ones.'],
            'care'=>['label'=>'Keep the care that made room','keepsake'=>'pressed-fern','name'=>'A Pressed Fern','description'=>'A green trace preserved without asking it to remain unchanged.'],
            'possibility'=>['label'=>'Keep what is still possible','keepsake'=>'blue-glass','name'=>'A Piece of Blue Glass','description'=>'A fragment that turns ordinary light toward an unwritten path.'],
        ];
        if(!isset($choices[$choice])) throw new RuntimeException('Choose what the Listening Wall should keep.');
        $this->database->transaction(function(PDO $pdo) use($accountId,$choice,$choices): void {
            $state=$this->lock($pdo,$accountId);
            if((string)$state['current_chapter']!=='the-listening-wall') throw new RuntimeException('Chapter Three has not begun.');
            $existing=$pdo->prepare('SELECT id FROM world_choice_history WHERE installation_id=:installation_id AND scene_key="listening-wall-truth" LIMIT 1');
            $existing->execute(['installation_id'=>$state['installation_id']]);
            if($existing->fetchColumn()) return;
            $now=gmdate('Y-m-d H:i:s');$selected=$choices[$choice];
            $pdo->prepare('INSERT INTO world_choice_history (id,installation_id,scene_key,choice_key,choice_label,created_at) VALUES (:id,:installation_id,"listening-wall-truth",:choice_key,:choice_label,:created_at)')->execute(['id'=>self::uuid(),'installation_id'=>$state['installation_id'],'choice_key'=>$choice,'choice_label'=>$selected['label'],'created_at'=>$now]);
            $pdo->prepare('UPDATE world_objectives SET status="completed",completed_at=:completed_at WHERE installation_id=:installation_id AND objective_key="choose-what-the-house-keeps"')->execute(['completed_at'=>$now,'installation_id'=>$state['installation_id']]);
            $pdo->prepare('INSERT INTO world_keepsakes (id,installation_id,keepsake_key,name,description,source_scene,acquired_at) VALUES (:id,:installation_id,:keepsake_key,:name,:description,"listening-wall-truth",:acquired_at)')->execute(['id'=>self::uuid(),'installation_id'=>$state['installation_id'],'keepsake_key'=>$selected['keepsake'],'name'=>$selected['name'],'description'=>$selected['description'],'acquired_at'=>$now]);
            $pdo->prepare('UPDATE world_narrative_progress SET current_scene="the-wall-remembers",updated_at=:updated_at WHERE installation_id=:installation_id')->execute(['updated_at'=>$now,'installation_id'=>$state['installation_id']]);
            $pdo->prepare('UPDATE world_relationships SET trust_score=trust_score+1,relationship_stage=CASE WHEN trust_score+1>=5 THEN "trusted" ELSE relationship_stage END,updated_at=:updated_at WHERE installation_id=:installation_id AND npc_key="caretaker"')->execute(['updated_at'=>$now,'installation_id'=>$state['installation_id']]);
            $this->relationshipMoment($pdo,(string)$state['installation_id'],1,'chapter.three.choice','You asked the house to remember: '.$selected['label'].'. The Caretaker preserved the meaning without turning it into a demand.',$now);
            $pdo->prepare('INSERT INTO world_story_history (id,installation_id,history_type,history_key,title,explanation,source_reaction_id,occurred_at) VALUES (:id,:installation_id,"choice","listening-wall-truth","The Listening Wall answered",:explanation,NULL,:occurred_at)')->execute(['id'=>self::uuid(),'installation_id'=>$state['installation_id'],'explanation'=>'The house now keeps this fictional truth: '.$selected['label'].'.','occurred_at'=>$now]);
        });
    }

    private function lock(PDO $pdo,string $accountId): array
    {
        $statement=$pdo->prepare('SELECT wi.id installation_id,np.current_chapter,np.current_scene,(SELECT choice_key FROM world_choice_history WHERE installation_id=wi.id AND scene_key="caretaker-welcome" LIMIT 1) support_choice FROM world_installations wi JOIN world_narrative_progress np ON np.installation_id=wi.id WHERE wi.account_id=:account_id AND wi.world_key="epic-ordinary" AND wi.status="active" LIMIT 1 FOR UPDATE');
        $statement->execute(['account_id'=>$accountId]);$state=$statement->fetch();
        if(!$state) throw new RuntimeException('Epic Ordinary is not active.');
        return $state;
    }

    private function relationshipMoment(PDO $pdo,string $installationId,int $delta,string $reason,string $explanation,string $now): void
    {
        $pdo->prepare('INSERT INTO world_relationship_history (id,installation_id,npc_key,delta_value,reason_code,source_event_id,explanation,created_at) VALUES (:id,:installation_id,"caretaker",:delta,:reason,NULL,:explanation,:created_at)')->execute(['id'=>self::uuid(),'installation_id'=>$installationId,'delta'=>$delta,'reason'=>$reason,'explanation'=>$explanation,'created_at'=>$now]);
    }

    private static function uuid(): string
    {
        $bytes=random_bytes(16);$bytes[6]=chr((ord($bytes[6])&0x0f)|0x40);$bytes[8]=chr((ord($bytes[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($bytes),4));
    }
}
