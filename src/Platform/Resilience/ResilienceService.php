<?php

declare(strict_types=1);

namespace Koravik\Platform\Resilience;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class ResilienceService
{
    public function __construct(private readonly Database $database) {}

    public function saveDraft(string $accountId,string $formKey,array $payload): void
    {
        $payload=$this->safePayload($payload);
        $this->database->pdo()->prepare(
            'INSERT INTO platform_form_drafts (id,account_id,form_key,payload_json,expires_at,created_at,updated_at)
             VALUES (:id,:account_id,:form_key,:payload,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 30 DAY),UTC_TIMESTAMP(),UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE payload_json=VALUES(payload_json),expires_at=VALUES(expires_at),updated_at=UTC_TIMESTAMP()'
        )->execute(['id'=>self::uuid(),'account_id'=>$accountId,'form_key'=>$formKey,'payload'=>json_encode($payload,JSON_THROW_ON_ERROR)]);
    }

    public function drafts(string $accountId): array
    {
        $s=$this->database->pdo()->prepare('SELECT id,form_key,payload_json,updated_at,expires_at FROM platform_form_drafts WHERE account_id=:account_id AND expires_at>UTC_TIMESTAMP() ORDER BY updated_at DESC');
        $s->execute(['account_id'=>$accountId]);$rows=$s->fetchAll();
        foreach($rows as &$row)$row['payload']=json_decode((string)$row['payload_json'],true)?:[];
        return $rows;
    }

    public function deleteDraft(string $accountId,string $id): void
    {
        $this->database->pdo()->prepare('DELETE FROM platform_form_drafts WHERE id=:id AND account_id=:account_id')->execute(['id'=>$id,'account_id'=>$accountId]);
    }

    public function claim(string $accountId,string $actionKey,string $requestKey): bool
    {
        if(!preg_match('/^[a-f0-9]{64}$/',$requestKey)) throw new RuntimeException('The submission key is invalid.');
        try {
            $this->database->pdo()->prepare(
                'INSERT INTO platform_idempotency_keys (id,account_id,action_key,request_key,created_at,expires_at)
                 VALUES (:id,:account_id,:action_key,:request_key,UTC_TIMESTAMP(),DATE_ADD(UTC_TIMESTAMP(),INTERVAL 1 DAY))'
            )->execute(['id'=>self::uuid(),'account_id'=>$accountId,'action_key'=>$actionKey,'request_key'=>$requestKey]);
            return true;
        } catch(\PDOException $e) {
            if($e->getCode()==='23000') return false;
            throw $e;
        }
    }

    public function touchSession(string $accountId,string $sessionId,?string $agent,?string $ip): bool
    {
        $hash=hash('sha256',$sessionId);
        $pdo=$this->database->pdo();
        $s=$pdo->prepare('SELECT revoked_at FROM auth_sessions WHERE session_hash=:hash LIMIT 1');$s->execute(['hash'=>$hash]);$row=$s->fetch();
        if($row && $row['revoked_at']!==null) return false;
        $pdo->prepare(
            'INSERT INTO auth_sessions (id,account_id,session_hash,user_agent,ip_address,created_at,last_seen_at)
             VALUES (:id,:account_id,:hash,:agent,:ip,UTC_TIMESTAMP(),UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE last_seen_at=UTC_TIMESTAMP(),user_agent=VALUES(user_agent),ip_address=VALUES(ip_address)'
        )->execute(['id'=>self::uuid(),'account_id'=>$accountId,'hash'=>$hash,'agent'=>mb_substr((string)$agent,0,255),'ip'=>mb_substr((string)$ip,0,64)]);
        return true;
    }

    public function sessions(string $accountId,string $sessionId): array
    {
        $s=$this->database->pdo()->prepare('SELECT id,session_hash,user_agent,ip_address,created_at,last_seen_at FROM auth_sessions WHERE account_id=:account_id AND revoked_at IS NULL ORDER BY last_seen_at DESC');
        $s->execute(['account_id'=>$accountId]);$current=hash('sha256',$sessionId);$rows=$s->fetchAll();
        foreach($rows as &$row)$row['current']=hash_equals($current,(string)$row['session_hash']);
        return $rows;
    }

    public function revokeSession(string $accountId,string $id,string $currentSessionId): bool
    {
        $s=$this->database->pdo()->prepare('UPDATE auth_sessions SET revoked_at=UTC_TIMESTAMP() WHERE id=:id AND account_id=:account_id AND session_hash<>:current');
        $s->execute(['id'=>$id,'account_id'=>$accountId,'current'=>hash('sha256',$currentSessionId)]);
        return $s->rowCount()===1;
    }

    public function revokeOthers(string $accountId,string $currentSessionId): int
    {
        $s=$this->database->pdo()->prepare('UPDATE auth_sessions SET revoked_at=UTC_TIMESTAMP() WHERE account_id=:account_id AND revoked_at IS NULL AND session_hash<>:current');
        $s->execute(['account_id'=>$accountId,'current'=>hash('sha256',$currentSessionId)]);
        return $s->rowCount();
    }

    private function safePayload(array $payload): array
    {
        unset($payload['csrf'],$payload['request_key'],$payload['password'],$payload['current_password'],$payload['new_password'],$payload['token']);
        $json=json_encode($payload,JSON_THROW_ON_ERROR);
        if(strlen($json)>20000) throw new RuntimeException('That draft is too large to save.');
        return $payload;
    }

    private static function uuid(): string
    {
        $b=random_bytes(16);$b[6]=chr((ord($b[6])&15)|64);$b[8]=chr((ord($b[8])&63)|128);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($b),4));
    }
}
