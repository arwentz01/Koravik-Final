<?php

declare(strict_types=1);

namespace Koravik\Platform\Hearth;

use DateTimeImmutable;
use DateTimeZone;
use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;
use Throwable;

final class DailyFocusService
{
    public function __construct(private readonly Database $database) {}

    public function dashboard(string $accountId): array
    {
        $date=$this->today($accountId);$repository=new DailyFocusRepository($this->database->pdo());
        return ['date'=>$date,'focus'=>$repository->find($accountId,$date),'candidates'=>$repository->candidates($accountId,$date)];
    }

    public function save(string $accountId,string $intention,array $occurrenceIds): void
    {
        $input=DailyFocus::normalize($intention,$occurrenceIds);$date=$this->today($accountId);
        $this->database->transaction(function(PDO $pdo)use($accountId,$date,$input):void{
            $repository=new DailyFocusRepository($pdo);
            $owned=$repository->ownedCandidateIds($accountId,$input['occurrence_ids']);
            if(count($owned)!==count($input['occurrence_ids']))throw new RuntimeException('One selected Quest is no longer available. Refresh and choose again.');
            if(count(array_unique(array_column($owned,'quest_id')))!==count($owned))throw new RuntimeException('Choose each Quest only once.');
            $ownedMap=array_fill_keys(array_column($owned,'id'),true);
            $ordered=array_values(array_filter($input['occurrence_ids'],static fn(string $id):bool=>isset($ownedMap[$id])));
            $focusId=$repository->replace($accountId,$date,$input['intention'],$ordered);
            $this->audit($pdo,$accountId,'hearth.daily_focus.saved',$focusId);
        });
    }

    public function clear(string $accountId): void
    {
        $date=$this->today($accountId);
        $this->database->transaction(function(PDO $pdo)use($accountId,$date):void{
            $repository=new DailyFocusRepository($pdo);
            if($repository->clear($accountId,$date))$this->audit($pdo,$accountId,'hearth.daily_focus.cleared',$accountId);
        });
    }

    private function today(string $accountId): string
    {
        $timezone='UTC';
        try{$s=$this->database->pdo()->prepare('SELECT timezone FROM account_settings WHERE account_id=:account_id');$s->execute(['account_id'=>$accountId]);$timezone=(string)($s->fetchColumn()?:'UTC');new DateTimeZone($timezone);}catch(Throwable){$timezone='UTC';}
        return (new DateTimeImmutable('now',new DateTimeZone($timezone)))->format('Y-m-d');
    }

    private function audit(PDO $pdo,string $accountId,string $action,string $subject):void
    {
        $pdo->prepare('INSERT INTO audit_log (id,account_id,action,subject_type,subject_id,occurred_at) VALUES (:id,:account_id,:action,"hearth_daily_focus",:subject,UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'action'=>$action,'subject'=>$subject]);
    }

    private static function uuid():string{$b=random_bytes(16);$b[6]=chr((ord($b[6])&15)|64);$b[8]=chr((ord($b[8])&63)|128);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($b),4));}
}
