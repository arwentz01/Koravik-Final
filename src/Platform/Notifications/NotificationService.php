<?php

declare(strict_types=1);

namespace Koravik\Platform\Notifications;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class NotificationService
{
    public const CATEGORIES = [
        'world.reactions' => 'World reactions',
        'platform.return' => 'Welcome-back summaries',
        'household.coordination' => 'Household coordination',
    ];

    public function __construct(private readonly Database $database) {}

    public function ensurePreferences(string $accountId): void
    {
        $statement = $this->database->pdo()->prepare('INSERT INTO notification_preferences (account_id,category,enabled,updated_at) VALUES (:account_id,:category,1,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE account_id=VALUES(account_id)');
        foreach (array_keys(self::CATEGORIES) as $category) $statement->execute(['account_id'=>$accountId,'category'=>$category]);
    }

    public function synchronize(string $accountId): void
    {
        $this->ensurePreferences($accountId);
        $reactions=$this->database->pdo()->prepare('SELECT wr.source_event_id,wr.title,wr.message FROM world_reactions wr JOIN world_installations wi ON wi.id=wr.installation_id WHERE wi.account_id=:account_id AND NOT EXISTS (SELECT 1 FROM notifications n WHERE n.account_id=:notification_account AND n.source_event_id=wr.source_event_id AND n.category="world.reactions") ORDER BY wr.created_at ASC LIMIT 50');
        $reactions->execute(['account_id'=>$accountId,'notification_account'=>$accountId]);
        foreach($reactions->fetchAll() as $row) $this->create($accountId,'Epic Ordinary','world.reactions',(string)$row['title'],(string)$row['message'],'/world/reaction','Sent because Epic Ordinary was active and permitted to interpret a completed Quest occurrence.',(string)$row['source_event_id']);

        $returns=$this->database->pdo()->prepare('SELECT po.id,po.payload_json FROM platform_outbox po WHERE po.account_id=:account_id AND po.event_name="Platform.PlayerReturned" AND po.status="delivered" AND NOT EXISTS (SELECT 1 FROM notifications n WHERE n.account_id=:notification_account AND n.source_event_id=po.id AND n.category="platform.return") ORDER BY po.occurred_at ASC LIMIT 20');
        $returns->execute(['account_id'=>$accountId,'notification_account'=>$accountId]);
        foreach($returns->fetchAll() as $row) {
            $payload=json_decode((string)$row['payload_json'],true,512,JSON_THROW_ON_ERROR);
            $days=max(7,(int)($payload['days_away']??7));
            $this->create($accountId,'Koravik','platform.return','Your welcome-back review is ready','A calm summary is available after about '.$days.' days away.','/return','Sent because Koravik detected a meaningful absence of at least seven days.',(string)$row['id']);
        }
    }

    public function enabled(string $accountId,string $category): bool
    {
        $this->ensurePreferences($accountId);
        $statement=$this->database->pdo()->prepare('SELECT enabled FROM notification_preferences WHERE account_id=:account_id AND category=:category');
        $statement->execute(['account_id'=>$accountId,'category'=>$category]);
        return (bool)$statement->fetchColumn();
    }

    public function create(string $accountId,string $sourceModule,string $category,string $title,string $body,string $targetUrl,string $reason,?string $sourceEventId): void
    {
        if(!$this->enabled($accountId,$category)) return;
        $this->database->pdo()->prepare('INSERT IGNORE INTO notifications (id,account_id,source_module,category,title,body,target_url,reason,source_event_id,created_at) VALUES (:id,:account_id,:source_module,:category,:title,:body,:target_url,:reason,:source_event_id,UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'source_module'=>$sourceModule,'category'=>$category,'title'=>$title,'body'=>$body,'target_url'=>$targetUrl,'reason'=>$reason,'source_event_id'=>$sourceEventId]);
    }

    public function list(string $accountId): array
    {
        $this->synchronize($accountId);
        $statement=$this->database->pdo()->prepare('SELECT id,source_module,category,title,body,target_url,reason,read_at,created_at FROM notifications WHERE account_id=:account_id AND dismissed_at IS NULL ORDER BY read_at IS NULL DESC, created_at DESC LIMIT 100');
        $statement->execute(['account_id'=>$accountId]);
        return $statement->fetchAll();
    }

    public function unreadCount(string $accountId): int
    {
        $this->synchronize($accountId);
        $statement=$this->database->pdo()->prepare('SELECT COUNT(*) FROM notifications WHERE account_id=:account_id AND read_at IS NULL AND dismissed_at IS NULL');
        $statement->execute(['account_id'=>$accountId]);
        return (int)$statement->fetchColumn();
    }

    public function changeState(string $accountId,string $notificationId,string $action): void
    {
        $sql=match($action){'read'=>'UPDATE notifications SET read_at=COALESCE(read_at,UTC_TIMESTAMP()) WHERE id=:id AND account_id=:account_id AND dismissed_at IS NULL','unread'=>'UPDATE notifications SET read_at=NULL WHERE id=:id AND account_id=:account_id AND dismissed_at IS NULL','dismiss'=>'UPDATE notifications SET dismissed_at=UTC_TIMESTAMP() WHERE id=:id AND account_id=:account_id',default=>throw new RuntimeException('Unknown notification action.')};
        $statement=$this->database->pdo()->prepare($sql);$statement->execute(['id'=>$notificationId,'account_id'=>$accountId]);
        if($statement->rowCount()!==1) throw new RuntimeException('That notification is no longer available.');
    }

    public function markAllRead(string $accountId): void
    {
        $this->database->pdo()->prepare('UPDATE notifications SET read_at=COALESCE(read_at,UTC_TIMESTAMP()) WHERE account_id=:account_id AND dismissed_at IS NULL')->execute(['account_id'=>$accountId]);
    }

    public function preferences(string $accountId): array
    {
        $this->ensurePreferences($accountId);
        $statement=$this->database->pdo()->prepare('SELECT category,enabled FROM notification_preferences WHERE account_id=:account_id');$statement->execute(['account_id'=>$accountId]);$values=[];
        foreach($statement->fetchAll() as $row) $values[(string)$row['category']]=(bool)$row['enabled'];
        return $values;
    }

    public function savePreferences(string $accountId,array $enabledCategories): void
    {
        $this->database->transaction(function(PDO $pdo) use($accountId,$enabledCategories): void {$statement=$pdo->prepare('INSERT INTO notification_preferences (account_id,category,enabled,updated_at) VALUES (:account_id,:category,:enabled,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE enabled=VALUES(enabled),updated_at=VALUES(updated_at)');foreach(array_keys(self::CATEGORIES) as $category) $statement->execute(['account_id'=>$accountId,'category'=>$category,'enabled'=>in_array($category,$enabledCategories,true)?1:0]);});
    }

    private static function uuid(): string
    {
        $bytes=random_bytes(16);$bytes[6]=chr((ord($bytes[6])&0x0f)|0x40);$bytes[8]=chr((ord($bytes[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($bytes),4));
    }
}
