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
    public function get(string $accountId,string $id): array {$s=$this->database->pdo()->prepare('SELECT * FROM chronicle_entries WHERE id=:id AND account_id=:account_id AND deleted_at IS NULL');$s->execute(['id'=>$id,'account_id'=>$accountId]);$row=$s->fetch();if(!$row)throw new RuntimeException('Chronicle entry not found.');$t=$this->database->pdo()->prepare('SELECT tag_name FROM chronicle_tags WHERE entry_id=:id ORDER BY tag_name');$t->execute(['id'=>$id]);$row['tags']=$t->fetchAll(PDO::FETCH_COLUMN);return $row;}
    public function create(string $accountId,array $input): string
    {
        $title=trim((string)($input['title']??''));$body=trim((string)($input['body']??''));if($title===''||mb_strlen($title)>180)throw new RuntimeException('Use a title between 1 and 180 characters.');if($body===''||mb_strlen($body)>8000)throw new RuntimeException('Use reflection text between 1 and 8000 characters.');$id=self::uuid();
        $this->database->transaction(function(PDO $pdo) use($id,$accountId,$title,$body,$input): void {$pdo->prepare('INSERT INTO chronicle_entries (id,account_id,entry_type,title,body,provenance_type,provenance_label,editable,status,created_at,updated_at) VALUES (:id,:account_id,"personal",:title,:body,"player_authored","Written directly in Chronicle",1,"active",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$id,'account_id'=>$accountId,'title'=>$title,'body'=>$body]);$this->tags($pdo,$id,(string)($input['tags']??''));$this->audit($pdo,$accountId,'chronicle.entry.created',$id);});return $id;
    }
    public function update(string $accountId,string $id,array $input): void
    {
        $entry=$this->get($accountId,$id);if(!(bool)$entry['editable'])throw new RuntimeException('This historical entry is read-only.');$title=trim((string)($input['title']??''));$body=trim((string)($input['body']??''));if($title===''||$body==='')throw new RuntimeException('Title and reflection text are required.');
        $this->database->transaction(function(PDO $pdo) use($accountId,$id,$title,$body,$input): void {$pdo->prepare('UPDATE chronicle_entries SET title=:title,body=:body,updated_at=UTC_TIMESTAMP() WHERE id=:id AND account_id=:account_id AND editable=1')->execute(['title'=>$title,'body'=>$body,'id'=>$id,'account_id'=>$accountId]);$pdo->prepare('DELETE FROM chronicle_tags WHERE entry_id=:id')->execute(['id'=>$id]);$this->tags($pdo,$id,(string)($input['tags']??''));$this->audit($pdo,$accountId,'chronicle.entry.updated',$id);});
    }
    public function lifecycle(string $accountId,string $id,string $action): void
    {
        if(!in_array($action,['archive','restore','delete'],true))throw new RuntimeException('Choose a valid Chronicle action.');$entry=$this->get($accountId,$id);if(!(bool)$entry['editable']&&$action==='delete')throw new RuntimeException('Generated historical entries may be reversed by their source, not deleted here.');$status=$action==='restore'?'active':($action==='archive'?'archived':'deleted');$sql=$action==='delete'?'UPDATE chronicle_entries SET status="deleted",deleted_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE id=:id AND account_id=:account_id':'UPDATE chronicle_entries SET status=:status,archived_at=:archived,updated_at=UTC_TIMESTAMP() WHERE id=:id AND account_id=:account_id';$params=['id'=>$id,'account_id'=>$accountId];if($action!=='delete'){$params['status']=$status;$params['archived']=$action==='archive'?gmdate('Y-m-d H:i:s'):null;}$s=$this->database->pdo()->prepare($sql);$s->execute($params);
    }
    private function tags(PDO $pdo,string $id,string $raw): void {foreach(array_unique(array_filter(array_map('trim',explode(',',$raw)))) as $tag){if(mb_strlen($tag)<=60)$pdo->prepare('INSERT IGNORE INTO chronicle_tags (entry_id,tag_name,created_at) VALUES (:id,:tag,UTC_TIMESTAMP())')->execute(['id'=>$id,'tag'=>$tag]);}}
    private function audit(PDO $pdo,string $accountId,string $action,string $id): void {$pdo->prepare('INSERT INTO audit_log (id,account_id,action,subject_type,subject_id,occurred_at) VALUES (:audit,:account_id,:action,"chronicle_entry",:subject,UTC_TIMESTAMP())')->execute(['audit'=>self::uuid(),'account_id'=>$accountId,'action'=>$action,'subject'=>$id]);}
    private static function uuid(): string {$b=random_bytes(16);$b[6]=chr((ord($b[6])&15)|64);$b[8]=chr((ord($b[8])&63)|128);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($b),4));}
}
