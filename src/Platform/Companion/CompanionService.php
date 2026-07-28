<?php

declare(strict_types=1);
namespace Koravik\Platform\Companion;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class CompanionService
{
    public function __construct(private readonly Database $database) {}

    public function list(string $accountId): array
    {
        $s=$this->database->pdo()->prepare('SELECT * FROM companion_proposals WHERE account_id=:account_id ORDER BY created_at DESC LIMIT 50');
        $s->execute(['account_id'=>$accountId]);
        return $s->fetchAll();
    }

    public function get(string $accountId,string $id): array
    {
        $s=$this->database->pdo()->prepare('SELECT * FROM companion_proposals WHERE id=:id AND account_id=:account_id LIMIT 1');
        $s->execute(['id'=>$id,'account_id'=>$accountId]);
        $row=$s->fetch();
        if(!$row) throw new RuntimeException('That proposal is unavailable.');
        $row['payload']=json_decode((string)$row['proposed_payload_json'],true,512,JSON_THROW_ON_ERROR);
        return $row;
    }

    public function proposeQuest(string $accountId,string $request): string
    {
        $request=trim($request);
        if(mb_strlen($request)<8 || mb_strlen($request)>1200) throw new RuntimeException('Describe the help you need in 8 to 1200 characters.');
        $title=$this->suggestTitle($request);
        $payload=['title'=>$title,'notes'=>'A bounded first step based on your request: '.$request,'quest_type'=>'action'];
        $id=self::uuid();$now=gmdate('Y-m-d H:i:s');
        $this->database->transaction(function(PDO $pdo) use($accountId,$request,$title,$payload,$id,$now): void {
            $pdo->prepare('INSERT INTO companion_proposals (id,account_id,proposal_type,status,version,request_text,title,proposed_payload_json,reasoning,source_context,owning_module,consequence,expires_at,created_at,updated_at) VALUES (:id,:account_id,"quest.create","awaiting_approval",1,:request_text,:title,:payload,:reasoning,:source_context,"Quests",:consequence,DATE_ADD(:created_at,INTERVAL 14 DAY),:created_at,:updated_at)')->execute([
                'id'=>$id,'account_id'=>$accountId,'request_text'=>$request,'title'=>$title,'payload'=>json_encode($payload,JSON_THROW_ON_ERROR),'reasoning'=>'A single visible action is easier to begin and easier to revise than a broad life project.','source_context'=>'Your request to Companion. No private records were searched.','consequence'=>'After a later execution step, Quests may create one personal Quest. Nothing is created by this proposal alone.','created_at'=>$now,'updated_at'=>$now,
            ]);
            $pdo->prepare('INSERT INTO audit_log (id,account_id,action,subject_type,subject_id,occurred_at) VALUES (:id,:account_id,"companion.proposal.created","companion_proposal",:subject_id,:occurred_at)')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'subject_id'=>$id,'occurred_at'=>$now]);
        });
        return $id;
    }

    public function edit(string $accountId,string $id,array $input): void
    {
        $proposal=$this->get($accountId,$id);
        if(!in_array($proposal['status'],['awaiting_approval','draft'],true)) throw new RuntimeException('That proposal can no longer be edited.');
        $title=trim((string)($input['title']??''));$notes=trim((string)($input['notes']??''));
        if($title==='' || mb_strlen($title)>180) throw new RuntimeException('Use a Quest title between 1 and 180 characters.');
        if(mb_strlen($notes)>3000) throw new RuntimeException('Keep proposed notes under 3000 characters.');
        $payload=['title'=>$title,'notes'=>$notes,'quest_type'=>'action'];
        $this->database->pdo()->prepare('UPDATE companion_proposals SET title=:title,proposed_payload_json=:payload,version=version+1,approved_version=NULL,approved_at=NULL,status="awaiting_approval",updated_at=UTC_TIMESTAMP() WHERE id=:id AND account_id=:account_id')->execute(['title'=>$title,'payload'=>json_encode($payload,JSON_THROW_ON_ERROR),'id'=>$id,'account_id'=>$accountId]);
    }

    public function decide(string $accountId,string $id,string $decision,int $version): void
    {
        $proposal=$this->get($accountId,$id);
        if((int)$proposal['version']!==$version) throw new RuntimeException('The proposal changed. Review the latest version before deciding.');
        if($proposal['status']!=='awaiting_approval') throw new RuntimeException('That proposal is no longer awaiting a decision.');
        $approved=$decision==='approve';
        if(!$approved && $decision!=='dismiss') throw new RuntimeException('Choose approve or dismiss.');
        $action=$approved?'companion.proposal.approved':'companion.proposal.dismissed';
        $this->database->transaction(function(PDO $pdo) use($accountId,$id,$version,$approved,$action): void {
            $pdo->prepare('UPDATE companion_proposals SET status=:status,approved_version=:approved_version,approved_at=:approved_at,dismissed_at=:dismissed_at,updated_at=UTC_TIMESTAMP() WHERE id=:id AND account_id=:account_id')->execute(['status'=>$approved?'approved':'dismissed','approved_version'=>$approved?$version:null,'approved_at'=>$approved?gmdate('Y-m-d H:i:s'):null,'dismissed_at'=>$approved?null:gmdate('Y-m-d H:i:s'),'id'=>$id,'account_id'=>$accountId]);
            $pdo->prepare('INSERT INTO audit_log (id,account_id,action,subject_type,subject_id,occurred_at) VALUES (:id,:account_id,:action,"companion_proposal",:subject_id,UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'action'=>$action,'subject_id'=>$id]);
        });
    }

    private function suggestTitle(string $request): string
    {
        $lower=mb_strtolower($request);
        if(str_contains($lower,'bedroom') || str_contains($lower,'room')) return 'Clear one visible surface';
        if(str_contains($lower,'email')) return 'Answer one important email';
        if(str_contains($lower,'exercise') || str_contains($lower,'walk')) return 'Move for ten minutes';
        return 'Take one visible next step';
    }

    private static function uuid(): string { $b=random_bytes(16);$b[6]=chr((ord($b[6])&15)|64);$b[8]=chr((ord($b[8])&63)|128);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($b),4)); }
}