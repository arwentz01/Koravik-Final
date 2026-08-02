<?php

declare(strict_types=1);

namespace Koravik\Platform\Media;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class MediaService
{
    public function __construct(private readonly Database $database) {}

    public function list(string $accountId): array
    {
        $s=$this->database->pdo()->prepare('SELECT * FROM platform_media_assets WHERE account_id=:account_id ORDER BY created_at DESC LIMIT 100');
        $s->execute(['account_id'=>$accountId]);
        return $s->fetchAll();
    }

    public function createReference(string $accountId,array $input): string
    {
        $name=trim((string)($input['original_name']??''));$type=trim((string)($input['media_type']??''));$reference=trim((string)($input['storage_reference']??''));$owner=trim((string)($input['owner_module']??'Chronicle'));$visibility=(string)($input['visibility']??'private');$alt=trim((string)($input['alt_text']??''));
        if($name===''||mb_strlen($name)>255)throw new RuntimeException('Give the media reference a clear file name.');
        if($type===''||mb_strlen($type)>80)throw new RuntimeException('Give the media reference a type.');
        if($reference===''||mb_strlen($reference)>500)throw new RuntimeException('Give the media reference a storage location or identifier.');
        if(!in_array($owner,['Chronicle','Beacon','Gather','Health','Platform'],true))throw new RuntimeException('Choose a supported media owner.');
        if(!in_array($visibility,['private','unlisted','public'],true))throw new RuntimeException('Choose a valid visibility.');
        $id=self::uuid();
        $this->database->transaction(function(PDO $pdo)use($id,$accountId,$name,$type,$reference,$owner,$visibility,$alt):void{
            $pdo->prepare('INSERT INTO platform_media_assets (id,account_id,owner_module,original_name,media_type,storage_reference,visibility,alt_text,created_at,updated_at) VALUES (:id,:account_id,:owner,:name,:type,:reference,:visibility,:alt,UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$id,'account_id'=>$accountId,'owner'=>$owner,'name'=>$name,'type'=>$type,'reference'=>$reference,'visibility'=>$visibility,'alt'=>$alt?:null]);
            $pdo->prepare('INSERT INTO audit_log (id,account_id,action,subject_type,subject_id,occurred_at) VALUES (:id,:account_id,"media.reference.created","media_asset",:subject,UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'subject'=>$id]);
        });
        return $id;
    }

    public function link(string $accountId,string $assetId,string $ownerModule,string $ownerRecordId,string $purpose=''): void
    {
        if(!in_array($ownerModule,['Quests','Chronicle','Gather','Beacon','Health'],true))throw new RuntimeException('Choose a supported media attachment owner.');
        $check=$this->database->pdo()->prepare('SELECT id FROM platform_media_assets WHERE id=:id AND account_id=:account_id');
        $check->execute(['id'=>$assetId,'account_id'=>$accountId]);
        if(!$check->fetchColumn())throw new RuntimeException('Choose one of your media references.');
        $this->database->pdo()->prepare('INSERT INTO platform_media_links (id,account_id,media_asset_id,owner_module,owner_record_id,purpose,created_at) VALUES (:id,:account_id,:asset,:owner,:record,:purpose,UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'asset'=>$assetId,'owner'=>$ownerModule,'record'=>$ownerRecordId,'purpose'=>trim($purpose)?:null]);
    }

    public function linksFor(string $accountId,string $ownerModule,string $ownerRecordId): array
    {
        $s=$this->database->pdo()->prepare('SELECT l.purpose,l.created_at,a.original_name,a.media_type,a.storage_reference,a.visibility,a.alt_text FROM platform_media_links l JOIN platform_media_assets a ON a.id=l.media_asset_id WHERE l.account_id=:account_id AND l.owner_module=:owner AND l.owner_record_id=:record ORDER BY l.created_at DESC');
        $s->execute(['account_id'=>$accountId,'owner'=>$ownerModule,'record'=>$ownerRecordId]);
        return $s->fetchAll();
    }

    private static function uuid(): string{$b=random_bytes(16);$b[6]=chr((ord($b[6])&15)|64);$b[8]=chr((ord($b[8])&63)|128);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($b),4));}
}
