<?php

declare(strict_types=1);

namespace Koravik\Worlds\EpicOrdinary;

use Koravik\Platform\Database\Database;
use RuntimeException;

final class WorldProgressService
{
    public function __construct(private readonly Database $database) {}

    public function progress(string $accountId): array
    {
        $pdo=$this->database->pdo();
        $s=$pdo->prepare('SELECT wi.id installation_id,wi.status,wi.installed_at,wc.package_version,np.current_arc,np.current_chapter,np.current_scene,wr.trust_score,wr.relationship_stage,(SELECT granted FROM world_fact_permissions WHERE installation_id=wi.id AND fact_key="quest.completed" LIMIT 1) quest_fact_granted FROM world_installations wi JOIN world_catalog wc ON wc.world_key=wi.world_key JOIN world_narrative_progress np ON np.installation_id=wi.id LEFT JOIN world_relationships wr ON wr.installation_id=wi.id AND wr.npc_key="caretaker" WHERE wi.account_id=:account_id AND wi.world_key="epic-ordinary" LIMIT 1');
        $s->execute(['account_id'=>$accountId]);$state=$s->fetch();
        if(!$state) throw new RuntimeException('Epic Ordinary is not installed for this account.');
        foreach([
            'objectives'=>'SELECT objective_key,title,description,status,completed_at,created_at FROM world_objectives WHERE installation_id=:id ORDER BY created_at DESC',
            'choices'=>'SELECT scene_key,choice_key,choice_label,created_at FROM world_choice_history WHERE installation_id=:id ORDER BY created_at DESC',
            'keepsakes'=>'SELECT keepsake_key,name,description,source_scene,acquired_at FROM world_keepsakes WHERE installation_id=:id ORDER BY acquired_at DESC',
            'relationships'=>'SELECT delta_value,reason_code,explanation,created_at FROM world_relationship_history WHERE installation_id=:id AND npc_key="caretaker" ORDER BY created_at DESC',
            'reactions'=>'SELECT id,title,message,explanation,source_fact_key,source_fact_summary,rule_key,COALESCE(interpreted_at,created_at) interpreted_at,created_at FROM world_reactions WHERE installation_id=:id ORDER BY created_at DESC',
            'history'=>'SELECT history_type,history_key,title,explanation,source_reaction_id,occurred_at FROM world_story_history WHERE installation_id=:id ORDER BY occurred_at DESC LIMIT 50'
        ] as $key=>$sql){$q=$pdo->prepare($sql);$q->execute(['id'=>$state['installation_id']]);$state[$key]=$q->fetchAll();}
        return $state;
    }

    public function reaction(string $accountId,string $reactionId): array
    {
        $q=$this->database->pdo()->prepare('SELECT r.id,r.title,r.message,r.explanation,r.source_fact_key,r.source_fact_summary,r.rule_key,COALESCE(r.interpreted_at,r.created_at) interpreted_at,r.created_at FROM world_reactions r JOIN world_installations wi ON wi.id=r.installation_id WHERE r.id=:id AND wi.account_id=:account_id AND wi.world_key="epic-ordinary" LIMIT 1');
        $q->execute(['id'=>$reactionId,'account_id'=>$accountId]);$reaction=$q->fetch();
        if(!$reaction) throw new RuntimeException('That World reaction is unavailable.');
        $reaction['received']=$reaction['source_fact_summary']?:'A minimized approved event fact. No Quest notes or Chronicle text were received.';
        $reaction['excluded']='Quest notes, Chronicle prose, Companion memory, account secrets, and unrelated private records.';
        return $reaction;
    }
}