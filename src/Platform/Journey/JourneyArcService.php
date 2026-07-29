<?php

declare(strict_types=1);

namespace Koravik\Platform\Journey;

use Koravik\Districts\Quests\QuestService;
use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class JourneyArcService
{
    public function __construct(private readonly Database $database) {}

    public function dashboard(string $accountId): array
    {
        $this->seedStoryInvitation($accountId);
        $this->openRooms($accountId);
        $pdo=$this->database->pdo();
        return [
            'invitations'=>$this->rows($pdo,'SELECT * FROM story_invitations WHERE account_id=:account_id ORDER BY FIELD(status,"open","snoozed","accepted","declined"),created_at DESC',$accountId),
            'proposals'=>$this->rows($pdo,'SELECT * FROM quest_source_proposals WHERE account_id=:account_id ORDER BY FIELD(status,"open","accepted","declined"),created_at DESC',$accountId),
            'conversations'=>$this->rows($pdo,'SELECT * FROM relationship_conversations WHERE account_id=:account_id AND character_key="caretaker" ORDER BY created_at DESC LIMIT 12',$accountId),
            'keepsakes'=>$this->rows($pdo,'SELECT * FROM healing_home_keepsake_placements WHERE account_id=:account_id ORDER BY placed_at DESC',$accountId),
            'cooperative'=>$this->rows($pdo,'SELECT c.*,q.title AS quest_title FROM cooperative_quest_invitations c JOIN quests q ON q.id=c.quest_id WHERE c.account_id=:account_id ORDER BY FIELD(c.status,"open","accepted","declined"),c.created_at DESC',$accountId),
        ];
    }

    public function decideInvitation(string $accountId,string $id,string $decision): ?string
    {
        if(!in_array($decision,['accept','decline','snooze'],true)) throw new RuntimeException('Choose a valid invitation response.');
        $pdo=$this->database->pdo();
        $s=$pdo->prepare('SELECT * FROM story_invitations WHERE id=:id AND account_id=:account_id LIMIT 1');
        $s->execute(['id'=>$id,'account_id'=>$accountId]);$row=$s->fetch();
        if(!$row||!in_array((string)$row['status'],['open','snoozed'],true)) throw new RuntimeException('That invitation is no longer waiting.');
        if($decision==='decline'){$pdo->prepare('UPDATE story_invitations SET status="declined",decided_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE id=:id AND account_id=:account_id AND status IN ("open","snoozed")')->execute(['id'=>$id,'account_id'=>$accountId]);return null;}
        if($decision==='snooze'){$pdo->prepare('UPDATE story_invitations SET status="snoozed",snoozed_until=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 7 DAY),updated_at=UTC_TIMESTAMP() WHERE id=:id AND account_id=:account_id AND status IN ("open","snoozed")')->execute(['id'=>$id,'account_id'=>$accountId]);return null;}
        $questId=(new QuestService($this->database))->create($accountId,(string)$row['suggested_quest_title'],'',[
            'quest_type'=>'journey','purpose'=>(string)($row['suggested_purpose']??''),'next_step'=>(string)($row['suggested_next_step']??''),'origin_type'=>'story','origin_reference'=>(string)$row['world_key'].':'.(string)$row['invitation_key'],'frequency'=>'none','starts_on'=>gmdate('Y-m-d')
        ]);
        $u=$pdo->prepare('UPDATE story_invitations SET status="accepted",accepted_quest_id=:quest_id,decided_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE id=:id AND account_id=:account_id AND status IN ("open","snoozed")');
        $u->execute(['quest_id'=>$questId,'id'=>$id,'account_id'=>$accountId]);
        if($u->rowCount()!==1) throw new RuntimeException('The Quest was created, but the invitation changed before it could be linked.');
        return $questId;
    }

    public function converse(string $accountId,string $choice): void
    {
        $responses=['gratitude'=>'The Caretaker rests a hand on the chair. “Then let us remember that something good was allowed to matter.”','repair'=>'The Caretaker nods. “We do not have to pretend every moment landed well. We can begin again without erasing it.”','disagree'=>'The Caretaker leans back. “You may disagree with me. Your judgment remains your own.”','quiet'=>'The fire settles into a softer glow. No answer is demanded of you.'];
        if(!isset($responses[$choice])) throw new RuntimeException('Choose a valid conversation response.');
        $last=$this->database->pdo()->prepare('SELECT memory_text FROM journey_relationship_memories WHERE account_id=:account_id AND character_key="caretaker" ORDER BY remembered_at DESC LIMIT 1');$last->execute(['account_id'=>$accountId]);$memory=$last->fetchColumn();
        $this->database->pdo()->prepare('INSERT INTO relationship_conversations (id,account_id,character_key,conversation_type,prompt_key,player_choice,character_response,remembered_context,created_at) VALUES (:id,:account_id,"caretaker","check_in","home_fireplace",:choice,:response,:memory,UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'choice'=>$choice,'response'=>$responses[$choice],'memory'=>$memory?:null]);
    }

    public function createSourceProposal(string $accountId,string $domain,string $reference,string $kind,string $title): void
    {
        $allowedKinds=['prepare','attend','volunteer','follow_up','contribute'];
        if(!in_array($domain,['beacon','gather'],true)) throw new RuntimeException('Unsupported source domain.');
        if(!in_array($kind,$allowedKinds,true)) throw new RuntimeException('Choose a valid proposal pattern.');
        $title=trim($title);$reference=trim($reference);
        if($title===''||mb_strlen($title)>180) throw new RuntimeException('Give this proposal a clear title.');
        if($reference===''||mb_strlen($reference)>180) throw new RuntimeException('Give this proposal a source reference.');
        $this->database->pdo()->prepare('INSERT IGNORE INTO quest_source_proposals (id,account_id,source_domain,source_reference,title,purpose,next_step,proposal_kind,status,created_at) VALUES (:id,:account_id,:domain,:reference,:title,:purpose,:next_step,:kind,"open",UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'domain'=>$domain,'reference'=>$reference,'title'=>$title,'purpose'=>$domain==='beacon'?'Prepare for or follow through on an event that matters.':'Contribute to something meaningful with other people.','next_step'=>$domain==='beacon'?'Review the event and choose one preparation step.':'Clarify the contribution you are willing to make.','kind'=>$kind]);
    }

    public function decideProposal(string $accountId,string $id,string $decision): ?string
    {
        if(!in_array($decision,['accept','decline'],true)) throw new RuntimeException('Choose a valid proposal response.');
        $pdo=$this->database->pdo();$s=$pdo->prepare('SELECT * FROM quest_source_proposals WHERE id=:id AND account_id=:account_id AND status="open" LIMIT 1');$s->execute(['id'=>$id,'account_id'=>$accountId]);$row=$s->fetch();
        if(!$row) throw new RuntimeException('That proposal is no longer waiting.');
        if($decision==='decline'){$pdo->prepare('UPDATE quest_source_proposals SET status="declined",decided_at=UTC_TIMESTAMP() WHERE id=:id AND account_id=:account_id AND status="open"')->execute(['id'=>$id,'account_id'=>$accountId]);return null;}
        $questId=(new QuestService($this->database))->create($accountId,(string)$row['title'],'',['quest_type'=>'action','purpose'=>(string)($row['purpose']??''),'next_step'=>(string)($row['next_step']??''),'origin_type'=>(string)$row['source_domain'],'origin_reference'=>(string)$row['source_reference'],'frequency'=>'none','starts_on'=>gmdate('Y-m-d')]);
        $u=$pdo->prepare('UPDATE quest_source_proposals SET status="accepted",created_quest_id=:quest_id,decided_at=UTC_TIMESTAMP() WHERE id=:id AND account_id=:account_id AND status="open"');$u->execute(['quest_id'=>$questId,'id'=>$id,'account_id'=>$accountId]);
        if($u->rowCount()!==1) throw new RuntimeException('The Quest was created, but the proposal changed before it could be linked.');
        return $questId;
    }

    private function seedStoryInvitation(string $accountId): void
    {
        $this->database->pdo()->prepare('INSERT IGNORE INTO story_invitations (id,account_id,world_key,invitation_key,title,body,suggested_quest_title,suggested_purpose,suggested_next_step,status,created_at,updated_at) VALUES (:id,:account_id,"epic-ordinary","tend-one-corner","A small invitation from the Caretaker","The house does not ask you to repair everything. It asks whether one neglected corner of life deserves gentle attention.","Tend one neglected corner","Make one part of ordinary life easier to return to.","Choose the smallest visible corner that would help.","open",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'account_id'=>$accountId]);
    }

    private function openRooms(string $accountId): void
    {
        $this->database->pdo()->prepare('UPDATE healing_home_rooms SET state="open",unlocked_at=COALESCE(unlocked_at,UTC_TIMESTAMP()),updated_at=UTC_TIMESTAMP() WHERE account_id=:account_id AND room_key IN ("garden","library")')->execute(['account_id'=>$accountId]);
    }

    private function rows(PDO $pdo,string $sql,string $accountId): array{$s=$pdo->prepare($sql);$s->execute(['account_id'=>$accountId]);return $s->fetchAll();}
    private static function uuid(): string{$d=random_bytes(16);$d[6]=chr((ord($d[6])&0x0f)|0x40);$d[8]=chr((ord($d[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4));}
}