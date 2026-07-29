<?php

declare(strict_types=1);

namespace Koravik\Platform\Mail;

use Koravik\Platform\Database\Database;
use RuntimeException;

final class MailOperationsService
{
    public function __construct(private readonly Database $database) {}

    public function summary(): array
    {
        $rows=$this->database->pdo()->query('SELECT status,COUNT(*) total FROM platform_mail_deliveries GROUP BY status')->fetchAll();
        $summary=['pending'=>0,'processing'=>0,'retry'=>0,'failed'=>0,'sent'=>0,'cancelled'=>0];
        foreach($rows as $row)$summary[(string)$row['status']]=(int)$row['total'];
        $summary['stale']=$this->staleCount();
        return $summary;
    }

    public function recent(int $limit=100): array
    {
        $limit=max(1,min(250,$limit));
        return $this->database->pdo()->query('SELECT id,message_type,event_id,resend_of_id,recipient_email,recipient_name,subject,status,attempts,available_at,claimed_at,sent_at,cancelled_at,created_at,updated_at,failure_reason FROM platform_mail_deliveries ORDER BY created_at DESC LIMIT '.$limit)->fetchAll();
    }

    public function delivery(string $id): array
    {
        $s=$this->database->pdo()->prepare('SELECT * FROM platform_mail_deliveries WHERE id=:id LIMIT 1');
        $s->execute(['id'=>$id]);$row=$s->fetch();
        if(!$row)throw new RuntimeException('Mail delivery was not found.');
        $row['safe_failure_reason']=$this->redact((string)($row['failure_reason']??''));
        $row['safe_recipient']=$this->redactEmail((string)$row['recipient_email']);
        return $row;
    }

    public function retry(string $id): void
    {
        $s=$this->database->pdo()->prepare('UPDATE platform_mail_deliveries SET status="retry",available_at=UTC_TIMESTAMP(),claimed_at=NULL,failure_reason=NULL,updated_at=UTC_TIMESTAMP() WHERE id=:id AND status IN ("failed","retry")');
        $s->execute(['id'=>$id]);
        if($s->rowCount()!==1)throw new RuntimeException('Only failed or retrying deliveries may be retried.');
    }

    public function cancel(string $id,string $accountId): void
    {
        $s=$this->database->pdo()->prepare('UPDATE platform_mail_deliveries SET status="cancelled",cancelled_at=UTC_TIMESTAMP(),cancelled_by_account_id=:account,claimed_at=NULL,updated_at=UTC_TIMESTAMP() WHERE id=:id AND status IN ("pending","retry","failed")');
        $s->execute(['id'=>$id,'account'=>$accountId]);
        if($s->rowCount()!==1)throw new RuntimeException('This delivery can no longer be cancelled.');
    }

    public function resend(string $id): string
    {
        $row=$this->delivery($id);
        if(!in_array((string)$row['status'],['sent','failed','cancelled'],true))throw new RuntimeException('Only completed, failed, or cancelled deliveries may be resent.');
        return (new MailQueue($this->database))->enqueue((string)$row['message_type'],(string)$row['recipient_email'],(string)($row['recipient_name']??''),(string)$row['subject'],(string)$row['html_body'],(string)$row['text_body'],$row['reply_to_email']?(string)$row['reply_to_email']:null,$row['reply_to_name']?(string)$row['reply_to_name']:null,$row['event_id']?(string)$row['event_id']:null,(string)$row['id']);
    }

    public function enqueueTest(string $accountId): string
    {
        $s=$this->database->pdo()->prepare('SELECT email,display_name FROM platform_accounts WHERE id=:id AND status="active" LIMIT 1');
        $s->execute(['id'=>$accountId]);$account=$s->fetch();
        if(!$account)throw new RuntimeException('Your active account email could not be found.');
        $body='<h1>Koravik mail test</h1><p>Platform Mail successfully accepted this test delivery.</p>';
        return (new MailQueue($this->database))->enqueue('platform.test',(string)$account['email'],(string)$account['display_name'],'Koravik Platform Mail test',$body,'Koravik Platform Mail successfully accepted this test delivery.');
    }

    public function recoverStale(int $minutes=15): int
    {
        $minutes=max(5,min(1440,$minutes));
        $s=$this->database->pdo()->prepare('UPDATE platform_mail_deliveries SET status="retry",available_at=UTC_TIMESTAMP(),claimed_at=NULL,recovered_at=UTC_TIMESTAMP(),failure_reason="Recovered after stale processing claim",updated_at=UTC_TIMESTAMP() WHERE status="processing" AND claimed_at<DATE_SUB(UTC_TIMESTAMP(),INTERVAL :minutes MINUTE)');
        $s->bindValue('minutes',$minutes,\PDO::PARAM_INT);$s->execute();
        return $s->rowCount();
    }

    private function staleCount(): int
    {
        return (int)$this->database->pdo()->query('SELECT COUNT(*) FROM platform_mail_deliveries WHERE status="processing" AND claimed_at<DATE_SUB(UTC_TIMESTAMP(),INTERVAL 15 MINUTE)')->fetchColumn();
    }

    private function redact(string $value): string
    {
        if($value==='')return '';
        $value=(string)preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i','[redacted email]',$value);
        $value=(string)preg_replace('/\b(?:\d{1,3}\.){3}\d{1,3}\b/','[redacted address]',$value);
        return mb_substr($value,0,500);
    }

    private function redactEmail(string $email): string
    {
        [$local,$domain]=array_pad(explode('@',$email,2),2,'');
        if($domain==='')return '[invalid address]';
        return mb_substr($local,0,1).'***@'.$domain;
    }
}
