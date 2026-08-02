<?php

declare(strict_types=1);
namespace Koravik\Platform\Experience;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class ChronicleManagementService
{
    public function __construct(private readonly Database $database) {}
    public function list(string $accountId,bool $archived=false): array {$s=$this->database->pdo()->prepare('SELECT * FROM chronicle_entries WHERE account_id=:account_id AND status=:status AND deleted_at IS NULL ORDER BY created_at DESC');$s->execute(['account_id'=>$accountId,'status'=>$archived?'archived':'active']);return $s->fetchAll();}
    public function search(string $accountId,array $filters): array
    {
        $term=trim((string)($filters['q']??''));$status=in_array((string)($filters['status']??'active'),['active','archived'],true)?(string)$filters['status']:'active';$type=trim((string)($filters['entry_type']??''));$tag=trim((string)($filters['tag']??''));
        $sql='SELECT DISTINCT e.* FROM chronicle_entries e LEFT JOIN chronicle_tags t ON t.entry_id=e.id WHERE e.account_id=:account_id AND e.status=:status AND e.deleted_at IS NULL';$params=['account_id'=>$accountId,'status'=>$status];
        if($term!==''){$sql.=' AND (e.title LIKE :term OR e.body LIKE :term OR e.provenance_label LIKE :term)';$params['term']='%'.$term.'%';}
        if($type!==''){$sql.=' AND e.entry_type=:type';$params['type']=$type;}
        if($tag!==''){$sql.=' AND t.tag_name=:tag';$params['tag']=$tag;}
        $sql.=' ORDER BY e.created_at DESC LIMIT 100';$s=$this->database->pdo()->prepare($sql);$s->execute($params);return $s->fetchAll();
    }
    public function get(string $accountId,string $id): array {$s=$this->database->pdo()->prepare('SELECT * FROM chronicle_entries WHERE id=:id AND account_id=:account_id AND deleted_at IS NULL');$s->execute(['id'=>$id,'account_id'=>$accountId]);$row=$s->fetch();if(!$row)throw new RuntimeException('Chronicle entry not found.');$t=$this->database->pdo()->prepare('SELECT tag_name FROM chronicle_tags WHERE entry_id=:id ORDER BY tag_name');$t->execute(['id'=>$id]);$row['tags']=$t->fetchAll(PDO::FETCH_COLUMN);return $row;}
    public function create(string $accountId,array $input): string
    {
        $title=trim((string)($input['title']??''));$body=trim((string)($input['body']??''));if($title===''||mb_strlen($title)>180)throw new RuntimeException('Use a title between 1 and 180 characters.');if($body===''||mb_strlen($body)>8000)throw new RuntimeException('Use reflection text between 1 and 8000 characters.');$id=self::uuid();
        $this->database->transaction(function(PDO $pdo) use($id,$accountId,$title,$body,$input): void {$pdo->prepare('INSERT INTO chronicle_entries (id,account_id,entry_type,title,body,provenance_type,provenance_label,editable,status,created_at,updated_at) VALUES (:id,:account_id,"personal",:title,:body,"player_authored","Written directly in Chronicle",1,"active",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$id,'account_id'=>$accountId,'title'=>$title,'body'=>$body]);$this->tags($pdo,$id,(string)($input['tags']??''));$this->audit($pdo,$accountId,'chronicle.entry.created',$id);});return $id;
    }
    public function reflectionProposals(string $accountId): array {$s=$this->database->pdo()->prepare('SELECT * FROM chronicle_reflection_reviews WHERE account_id=:account_id ORDER BY FIELD(status,"proposed","saved","dismissed"),created_at DESC LIMIT 100');$s->execute(['account_id'=>$accountId]);return $s->fetchAll();}
    public function createReflectionProposal(string $accountId,string $source,string $title,string $body,?string $reference=null): string
    {
        $title=trim($title);$body=trim($body);if($title===''||$body==='')throw new RuntimeException('Reflection proposals need a title and body.');
        $id=self::uuid();$this->database->pdo()->prepare('INSERT INTO chronicle_reflection_reviews (id,account_id,source_module,source_reference,title,body,status,created_at,updated_at) VALUES (:id,:account_id,:source,:reference,:title,:body,"proposed",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$id,'account_id'=>$accountId,'source'=>$source,'reference'=>$reference,'title'=>$title,'body'=>$body]);return $id;
    }
    public function proposeFromSource(string $accountId,array $input): string
    {
        $source=(string)($input['source_module']??'Companion');if(!in_array($source,['Quest completion','Gather follow-up','Companion','Healing Home Journal'],true))throw new RuntimeException('Choose a supported reflection source.');
        $reference=trim((string)($input['source_reference']??''))?:null;
        return $this->createReflectionProposal($accountId,$source,(string)($input['title']??''),(string)($input['body']??''),$reference);
    }
    public function saveReflectionProposal(string $accountId,string $id,array $input): string
    {
        return $this->database->transaction(function(PDO $pdo)use($accountId,$id,$input):string{$s=$pdo->prepare('SELECT * FROM chronicle_reflection_reviews WHERE id=:id AND account_id=:account_id AND status="proposed" FOR UPDATE');$s->execute(['id'=>$id,'account_id'=>$accountId]);$p=$s->fetch();if(!$p)throw new RuntimeException('Reflection proposal unavailable.');$title=trim((string)($input['title']??$p['title']));$body=trim((string)($input['body']??$p['body']));$privacy=(string)($input['privacy']??'private');if($title===''||mb_strlen($title)>180)throw new RuntimeException('Use a title between 1 and 180 characters.');if($body===''||mb_strlen($body)>8000)throw new RuntimeException('Use reflection text between 1 and 8000 characters.');if(!in_array($privacy,['private','unlisted'],true))throw new RuntimeException('Choose a valid privacy.');$entry=self::uuid();$pdo->prepare('INSERT INTO chronicle_entries (id,account_id,entry_type,title,body,provenance_type,provenance_label,editable,status,created_at,updated_at) VALUES (:id,:account_id,"reflection",:title,:body,"proposal_review",:label,1,"active",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$entry,'account_id'=>$accountId,'title'=>$title,'body'=>$body,'label'=>'Saved from '.$p['source_module'].' proposal']);$pdo->prepare('UPDATE chronicle_reflection_reviews SET title=:title,body=:body,privacy=:privacy,status="saved",chronicle_entry_id=:entry,updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['title'=>$title,'body'=>$body,'privacy'=>$privacy,'entry'=>$entry,'id'=>$id]);$this->audit($pdo,$accountId,'chronicle.proposal.saved',$entry);return $entry;});
    }
    public function dismissReflectionProposal(string $accountId,string $id): void {$this->database->transaction(function(PDO $pdo)use($accountId,$id):void{$s=$pdo->prepare('UPDATE chronicle_reflection_reviews SET status="dismissed",updated_at=UTC_TIMESTAMP() WHERE id=:id AND account_id=:account_id AND status="proposed"');$s->execute(['id'=>$id,'account_id'=>$accountId]);if($s->rowCount()!==1)throw new RuntimeException('Reflection proposal unavailable.');$this->audit($pdo,$accountId,'chronicle.proposal.dismissed',$id);});}
    public function update(string $accountId,string $id,array $input): void
    {
        $entry=$this->get($accountId,$id);if(!(bool)$entry['editable'])throw new RuntimeException('This historical entry is read-only.');$title=trim((string)($input['title']??''));$body=trim((string)($input['body']??''));if($title===''||mb_strlen($title)>180)throw new RuntimeException('Use a title between 1 and 180 characters.');if($body===''||mb_strlen($body)>8000)throw new RuntimeException('Use reflection text between 1 and 8000 characters.');
        $this->database->transaction(function(PDO $pdo) use($accountId,$id,$title,$body,$input): void {$pdo->prepare('UPDATE chronicle_entries SET title=:title,body=:body,updated_at=UTC_TIMESTAMP() WHERE id=:id AND account_id=:account_id AND editable=1')->execute(['title'=>$title,'body'=>$body,'id'=>$id,'account_id'=>$accountId]);$pdo->prepare('DELETE FROM chronicle_tags WHERE entry_id=:id')->execute(['id'=>$id]);$this->tags($pdo,$id,(string)($input['tags']??''));$this->audit($pdo,$accountId,'chronicle.entry.updated',$id);});
    }
    public function lifecycle(string $accountId,string $id,string $action): void
    {
        if(!in_array($action,['archive','restore','delete'],true))throw new RuntimeException('Choose a valid Chronicle action.');$entry=$this->get($accountId,$id);if(!(bool)$entry['editable']&&$action==='delete')throw new RuntimeException('Generated historical entries may be reversed by their source, not deleted here.');$this->database->transaction(function(PDO $pdo)use($accountId,$id,$action):void{$status=$action==='restore'?'active':($action==='archive'?'archived':'deleted');$sql=$action==='delete'?'UPDATE chronicle_entries SET status="deleted",deleted_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE id=:id AND account_id=:account_id':'UPDATE chronicle_entries SET status=:status,archived_at=:archived,updated_at=UTC_TIMESTAMP() WHERE id=:id AND account_id=:account_id';$params=['id'=>$id,'account_id'=>$accountId];if($action!=='delete'){$params['status']=$status;$params['archived']=$action==='archive'?gmdate('Y-m-d H:i:s'):null;}$pdo->prepare($sql)->execute($params);$this->audit($pdo,$accountId,'chronicle.entry.'.$action.'d',$id);});
    }
    private function tags(PDO $pdo,string $id,string $raw): void {foreach(array_unique(array_filter(array_map('trim',explode(',',$raw)))) as $tag){if(mb_strlen($tag)<=60)$pdo->prepare('INSERT IGNORE INTO chronicle_tags (entry_id,tag_name,created_at) VALUES (:id,:tag,UTC_TIMESTAMP())')->execute(['id'=>$id,'tag'=>$tag]);}}
    private function audit(PDO $pdo,string $accountId,string $action,string $id): void {$pdo->prepare('INSERT INTO audit_log (id,account_id,action,subject_type,subject_id,occurred_at) VALUES (:audit,:account_id,:action,"chronicle_entry",:subject,UTC_TIMESTAMP())')->execute(['audit'=>self::uuid(),'account_id'=>$accountId,'action'=>$action,'subject'=>$id]);}
    private static function uuid(): string {$b=random_bytes(16);$b[6]=chr((ord($b[6])&15)|64);$b[8]=chr((ord($b[8])&63)|128);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($b),4));}
}
