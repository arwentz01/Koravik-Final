<?php

declare(strict_types=1);

namespace Koravik\Platform\SourceReview;

use Koravik\Platform\Database\Database;
use PDO;

final class SourceReviewService
{
    public function __construct(private readonly Database $database) {}

    public function inbox(string $accountId, ?string $owner=null): array
    {
        $pdo=$this->database->pdo();
        $items=[];
        foreach($this->rows($pdo,'SELECT id,source_module,title,body,created_at FROM chronicle_reflection_reviews WHERE account_id=:account_id AND status="proposed" ORDER BY created_at DESC LIMIT 20',$accountId) as $row){
            $items[]=$this->item('Chronicle','reflection proposal',(string)$row['title'],(string)$row['body'],'/chronicle/proposals',(string)$row['created_at'],'Chronicle owns the saved reflection only after review.',(string)$row['id']);
        }
        foreach($this->rows($pdo,'SELECT id,proposal_type,title,reasoning,owning_module,status,updated_at FROM companion_proposals WHERE account_id=:account_id AND status IN ("draft","awaiting_approval","approved") ORDER BY updated_at DESC LIMIT 20',$accountId) as $row){
            $items[]=$this->item('Companion',(string)$row['proposal_type'],(string)$row['title'],(string)$row['reasoning'],'/companion/proposals/'.(string)$row['id'],(string)$row['updated_at'],'Companion proposes; '.(string)$row['owning_module'].' executes after approval and revalidation.',(string)$row['id']);
        }
        foreach($this->rows($pdo,'SELECT id,outcome_type,summary,status,created_at FROM gather_outcome_proposals WHERE account_id=:account_id AND status="proposed" ORDER BY created_at DESC LIMIT 20',$accountId) as $row){
            $items[]=$this->item('Gather',(string)$row['outcome_type'],'Gather outcome proposal',(string)$row['summary'],'/gather/outcomes/'.(string)$row['id'].'/review',(string)$row['created_at'],'Gather owns event truth; destination records require explicit approval.',(string)$row['id']);
        }
        foreach($this->rows($pdo,'SELECT f.id,f.title,f.message,f.created_at FROM gather_event_followups f WHERE f.author_account_id=:account_id AND f.status="draft" ORDER BY f.created_at DESC LIMIT 20',$accountId) as $row){
            $items[]=$this->item('Gather','follow-up draft',(string)$row['title'],(string)$row['message'],'/source-review/drafts/gather-followup/'.(string)$row['id'],(string)$row['created_at'],'Gather owns the follow-up; Quest or Chronicle drafts are opt-in review paths.',(string)$row['id']);
        }
        foreach($this->rows($pdo,'SELECT room_key,name,note_text,note_updated_at FROM healing_home_rooms WHERE account_id=:account_id AND note_text IS NOT NULL AND note_text<>"" ORDER BY note_updated_at DESC LIMIT 20',$accountId) as $row){
            $items[]=$this->item('Healing Home','room note',(string)$row['name'],(string)$row['note_text'],'/source-review/drafts/room-note/'.(string)$row['room_key'],(string)$row['note_updated_at'],'Healing Home owns the note; promotion creates only a reviewed draft path.',(string)$row['room_key']);
        }
        foreach($this->rows($pdo,'SELECT id,form_key,payload_json,updated_at,expires_at FROM platform_form_drafts WHERE account_id=:account_id AND form_key LIKE "source_review.%" AND expires_at>UTC_TIMESTAMP() ORDER BY updated_at DESC LIMIT 20',$accountId) as $row){
            $payload=json_decode((string)$row['payload_json'],true)?:[];
            $items[]=$this->item((string)($payload['source_owner']??'Source Review'),'durable draft',(string)($payload['title']??$row['form_key']),(string)($payload['body']??'Saved Source Review draft'),'/source-review/drafts/'.(string)$row['id'].'/resume',(string)$row['updated_at'],'Durable draft is recoverable until '.$row['expires_at'].' UTC; destination still requires explicit review.',(string)$row['id']);
        }
        foreach($this->rows($pdo,'SELECT id,title,body,target_url,source_module,created_at FROM notifications WHERE account_id=:account_id AND read_at IS NULL AND dismissed_at IS NULL ORDER BY created_at DESC LIMIT 20',$accountId) as $row){
            $items[]=$this->item((string)$row['source_module'],'unread notification',(string)$row['title'],(string)$row['body'],(string)($row['target_url']?:'/notifications'),(string)$row['created_at'],'Notification links back to its source owner; reading it changes only notification state.',(string)$row['id']);
        }
        usort($items,static fn(array $a,array $b):int=>strcmp((string)$b['updated_at'],(string)$a['updated_at']));
        $owner=trim((string)$owner);
        if($owner!=='')$items=array_values(array_filter($items,static fn(array $item):bool=>strtolower(str_replace(' ','_', (string)$item['source_owner']))===$owner));
        return array_slice($items,0,80);
    }

    public function counts(string $accountId): array
    {
        $items=$this->inbox($accountId);$counts=['total'=>count($items),'chronicle'=>0,'quest'=>0,'companion'=>0,'gather'=>0,'healing_home'=>0,'notifications'=>0];
        foreach($items as $item){$owner=strtolower(str_replace(' ','_', (string)$item['source_owner']));if(isset($counts[$owner]))$counts[$owner]++;if((string)$item['item_type']==='unread notification')$counts['notifications']++;}
        return $counts;
    }

    public function summary(string $accountId, ?string $owner=null): array
    {
        $items=$this->inbox($accountId,$owner);$counts=$this->counts($accountId);$buckets=['needs_decision'=>0,'approved_waiting'=>0,'draft_paths'=>0,'notices'=>0];
        foreach($items as $item){
            $type=(string)$item['item_type'];
            if(str_contains($type,'approved'))$buckets['approved_waiting']++;
            elseif(in_array($type,['follow-up draft','room note','durable draft'],true))$buckets['draft_paths']++;
            elseif($type==='unread notification')$buckets['notices']++;
            else $buckets['needs_decision']++;
        }
        return ['items'=>$items,'counts'=>$counts,'buckets'=>$buckets,'filter'=>$owner?:'all','top_item'=>$items[0]??null];
    }

    public function roomNoteDraft(string $accountId,string $roomKey): ?array
    {
        if(!preg_match('/^[a-z0-9_]+$/',$roomKey))return null;
        $s=$this->database->pdo()->prepare('SELECT room_key,name,note_text,note_updated_at FROM healing_home_rooms WHERE account_id=:account_id AND room_key=:room_key AND note_text IS NOT NULL AND note_text<>"" LIMIT 1');
        $s->execute(['account_id'=>$accountId,'room_key'=>$roomKey]);$row=$s->fetch();
        return $row?:null;
    }

    public function gatherFollowupDraft(string $accountId,string $id): ?array
    {
        $s=$this->database->pdo()->prepare('SELECT f.*,e.title event_title FROM gather_event_followups f JOIN gather_events e ON e.id=f.event_id WHERE f.author_account_id=:account_id AND f.id=:id LIMIT 1');
        $s->execute(['account_id'=>$accountId,'id'=>$id]);$row=$s->fetch();
        return $row?:null;
    }

    private function rows(PDO $pdo,string $sql,string $accountId): array
    {
        $s=$pdo->prepare($sql);$s->execute(['account_id'=>$accountId]);return $s->fetchAll();
    }

    private function item(string $owner,string $type,string $title,string $summary,string $href,string $updated,string $consequence,string $reference): array
    {
        return ['source_owner'=>$owner,'owner_key'=>strtolower(str_replace(' ','_',$owner)),'item_type'=>$type,'title'=>$title,'summary'=>mb_strimwidth(trim(strip_tags($summary)),0,220,'…'),'href'=>$href,'updated_at'=>$updated,'consequence'=>$consequence,'reference'=>$reference,'resume_token'=>substr(hash('sha256',$owner.'|'.$type.'|'.$reference),0,12)];
    }
}
