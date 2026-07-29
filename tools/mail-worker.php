<?php

declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

use Koravik\Platform\Mail\MailOperationsService;
use Koravik\Platform\Mail\SmtpMailer;

$limit=max(1,min(100,(int)($argv[1]??20)));$pdo=database()->pdo();$mailer=new SmtpMailer();
$recovered=(new MailOperationsService(database()))->recoverStale();
if($recovered>0)echo 'Recovered '.$recovered.' stale mail claim(s).'.PHP_EOL;
$stmt=$pdo->prepare('SELECT * FROM platform_mail_deliveries WHERE status IN ("pending","retry") AND available_at<=UTC_TIMESTAMP() ORDER BY created_at LIMIT '.$limit);
$stmt->execute();$messages=$stmt->fetchAll();
foreach($messages as $message){
    $claim=$pdo->prepare('UPDATE platform_mail_deliveries SET status="processing",claimed_at=UTC_TIMESTAMP(),attempts=attempts+1,updated_at=UTC_TIMESTAMP() WHERE id=:id AND status IN ("pending","retry")');$claim->execute(['id'=>$message['id']]);if($claim->rowCount()!==1)continue;
    try{$reference=$mailer->send($message);$pdo->prepare('UPDATE platform_mail_deliveries SET status="sent",sent_at=UTC_TIMESTAMP(),provider_reference=:reference,failure_reason=NULL,updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['reference'=>$reference,'id'=>$message['id']]);echo 'Sent '.$message['id'].PHP_EOL;}
    catch(Throwable $e){$attempts=((int)$message['attempts'])+1;$status=$attempts>=5?'failed':'retry';$delay=min(3600,60*(2**min($attempts,6)));$pdo->prepare('UPDATE platform_mail_deliveries SET status=:status,available_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL :delay SECOND),failure_reason=:reason,updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['status'=>$status,'delay'=>$delay,'reason'=>substr($e->getMessage(),0,500),'id'=>$message['id']]);echo 'Failed '.$message['id'].': '.$e->getMessage().PHP_EOL;}
}
