<?php

declare(strict_types=1);

namespace Koravik\Platform\Notifications;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Events\EventConsumer;

final class NotificationConsumer implements EventConsumer
{
    public function __construct(private readonly Database $database) {}

    public function consume(array $event): void
    {
        $name=(string)($event['event_name']??'');
        $version=(int)($event['event_version']??0);
        if($version!==1) return;
        $accountId=(string)($event['account_id']??'');
        if($accountId==='') return;
        $service=new NotificationService($this->database);

        if($name==='Quests.QuestCompleted') {
            $reaction=$this->database->pdo()->prepare('SELECT title,message FROM world_reactions WHERE source_event_id=:source_event_id LIMIT 1');
            $reaction->execute(['source_event_id'=>(string)$event['id']]);
            $row=$reaction->fetch();
            if($row) $service->create($accountId,'Epic Ordinary','world.reactions',(string)$row['title'],(string)$row['message'],'/world/reaction','Sent because Epic Ordinary was active and permitted to interpret a completed Quest occurrence.',(string)$event['id']);
            return;
        }

        if($name==='Platform.PlayerReturned') {
            $payload=json_decode((string)($event['payload_json']??'{}'),true,512,JSON_THROW_ON_ERROR);
            $days=max(7,(int)($payload['days_away']??7));
            $service->create($accountId,'Koravik','platform.return','Your welcome-back review is ready','A calm summary is available after about '.$days.' days away.','/return','Sent because Koravik detected a meaningful absence of at least seven days.',(string)$event['id']);
        }
    }
}
