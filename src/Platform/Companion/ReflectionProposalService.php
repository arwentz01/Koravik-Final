<?php

declare(strict_types=1);
namespace Koravik\Platform\Companion;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class ReflectionProposalService
{
    public function __construct(private readonly Database $database) {}

    public function create(string $accountId,string $sourceContext,string $text): string
    {
        $sourceContext=trim($sourceContext);$text=trim($text);
        if($sourceContext==='' || mb_strlen($sourceContext)>1000) throw new RuntimeException('Describe the source context in 1 to 1000 characters.');
        if($text==='' || mb_strlen($text)>4000) throw new RuntimeException('Use reflection text between 1 and 4000 characters.');
        $id=self::uuid();$now=gmdate('Y-m-d H:i:s');$payload=['title'=>'A reflection worth keeping','body'=>$text,'voice'=>'companion_draft'];
        $this->database->transaction(function(PDO $pdo) use($accountId,$sourceContext,$text,$id,$now,$payload): void {
            $pdo->prepare('INSERT INTO companion_proposals (id,account_id,proposal_type,status,version,request_text,title,proposed_payload_json,reasoning,source_context,owning_module,consequence,expires_at,created_at,updated_at) VALUES (:id,:account_id,"chronicle.reflection.create","awaiting_approval",1,:request_text,"A reflection worth keeping",:payload,:reasoning,:source_context,"Chronicle",:consequence,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 14 DAY),:created_at,:updated_at)')->execute(['id'=>$id,'account_id'=>$accountId,'request_text'=>$text,'payload'=>json_encode($payload,JSON_THROW_ON_ERROR),'reasoning'=>'A draft can help preserve meaning, but only you decide whether it belongs in Chronicle.','source_context'=>$sourceContext,'consequence'=>'Chronicle may save one private reflection after you review, edit, approve, and explicitly choose Save to Chronicle.','created_at'=>$now,'updated_at'=>$now]);
            $pdo->prepare('INSERT INTO audit_log (id,account_id,action,subject_type,subject_id,occurred_at) VALUES (:id,:account_id,"companion.proposal.created","companion_proposal",:subject_id,:occurred_at)')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'subject_id'=>$id,'occurred_at'=>$now]);
        });
        return $id;
    }

    private static function uuid(): string { $b=random_bytes(16);$b[6]=chr((ord($b[6])&15)|64);$b[8]=chr((ord($b[8])&63)|128);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($b),4)); }
}