<?php

declare(strict_types=1);

namespace Koravik\Platform\Security;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class AuthRecoveryService
{
    public function __construct(private readonly Database $database) {}

    public function request(string $email, ?string $ip = null): void
    {
        $email = mb_strtolower(trim($email));
        if ($email === '') return;
        $pdo = $this->database->pdo();
        $s = $pdo->prepare('SELECT id,email FROM platform_accounts WHERE email=:email AND status="active" LIMIT 1');
        $s->execute(['email'=>$email]);
        $account = $s->fetch();
        if (!$account) return;
        $recent = $pdo->prepare('SELECT COUNT(*) FROM auth_recovery_tokens WHERE account_id=:account_id AND created_at>DATE_SUB(UTC_TIMESTAMP(),INTERVAL 15 MINUTE)');
        $recent->execute(['account_id'=>$account['id']]);
        if ((int)$recent->fetchColumn() >= 3) return;
        $plain = bin2hex(random_bytes(32));
        $id = self::uuid();
        $now = gmdate('Y-m-d H:i:s');
        $payload = json_encode(['path'=>'/recover/reset?token='.$plain,'expires_minutes'=>30], JSON_THROW_ON_ERROR);
        $this->database->transaction(function(PDO $tx) use($account,$plain,$id,$now,$payload,$ip): void {
            $tx->prepare('INSERT INTO auth_recovery_tokens (id,account_id,token_hash,expires_at,requested_ip_hash,created_at) VALUES (:id,:account_id,:token_hash,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 30 MINUTE),:ip_hash,:created_at)')->execute(['id'=>$id,'account_id'=>$account['id'],'token_hash'=>hash('sha256',$plain),'ip_hash'=>$ip?hash('sha256',$ip):null,'created_at'=>$now]);
            $tx->prepare('INSERT INTO auth_delivery_messages (id,account_id,recipient,template_key,payload_json,created_at) VALUES (:id,:account_id,:recipient,"auth.password_reset",:payload,:created_at)')->execute(['id'=>self::uuid(),'account_id'=>$account['id'],'recipient'=>$account['email'],'payload'=>$payload,'created_at'=>$now]);
            $this->audit($tx,(string)$account['id'],'auth.recovery.requested',$id);
        });
    }

    public function inspect(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/',$token)) return null;
        $s=$this->database->pdo()->prepare('SELECT t.id,t.account_id,t.expires_at,a.email FROM auth_recovery_tokens t JOIN platform_accounts a ON a.id=t.account_id WHERE t.token_hash=:hash AND t.used_at IS NULL AND t.expires_at>UTC_TIMESTAMP() AND a.status="active" LIMIT 1');
        $s->execute(['hash'=>hash('sha256',$token)]);
        return $s->fetch() ?: null;
    }

    public function reset(string $token,string $password): void
    {
        $this->validatePassword($password);
        $record=$this->inspect($token);
        if(!$record) throw new RuntimeException('That recovery link is invalid or has expired.');
        $this->database->transaction(function(PDO $pdo) use($record,$password): void {
            $pdo->prepare('UPDATE auth_credentials SET password_hash=:hash,updated_at=UTC_TIMESTAMP() WHERE account_id=:account_id')->execute(['hash'=>password_hash($password,PASSWORD_DEFAULT),'account_id'=>$record['account_id']]);
            $pdo->prepare('UPDATE auth_recovery_tokens SET used_at=UTC_TIMESTAMP() WHERE id=:id AND used_at IS NULL')->execute(['id'=>$record['id']]);
            $pdo->prepare('UPDATE auth_recovery_tokens SET used_at=COALESCE(used_at,UTC_TIMESTAMP()) WHERE account_id=:account_id')->execute(['account_id'=>$record['account_id']]);
            $pdo->prepare('INSERT INTO auth_security_state (account_id,failed_attempts,locked_until,session_version,updated_at) VALUES (:account_id,0,NULL,2,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE failed_attempts=0,locked_until=NULL,session_version=session_version+1,updated_at=UTC_TIMESTAMP()')->execute(['account_id'=>$record['account_id']]);
            $this->audit($pdo,(string)$record['account_id'],'auth.password.reset',(string)$record['id']);
        });
    }

    public function changePassword(string $accountId,string $current,string $next): void
    {
        $this->validatePassword($next);
        $s=$this->database->pdo()->prepare('SELECT password_hash FROM auth_credentials WHERE account_id=:account_id');$s->execute(['account_id'=>$accountId]);
        if(!password_verify($current,(string)$s->fetchColumn())) throw new RuntimeException('Your current password did not match.');
        $this->database->transaction(function(PDO $pdo) use($accountId,$next): void {
            $pdo->prepare('UPDATE auth_credentials SET password_hash=:hash,updated_at=UTC_TIMESTAMP() WHERE account_id=:account_id')->execute(['hash'=>password_hash($next,PASSWORD_DEFAULT),'account_id'=>$accountId]);
            $pdo->prepare('INSERT INTO auth_security_state (account_id,session_version,updated_at) VALUES (:account_id,2,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE session_version=session_version+1,updated_at=UTC_TIMESTAMP()')->execute(['account_id'=>$accountId]);
            $this->audit($pdo,$accountId,'auth.password.changed',$accountId);
        });
    }

    private function validatePassword(string $password): void
    {
        if(strlen($password)<8 || strlen($password)>200) throw new RuntimeException('Use a password with at least 8 characters.');
    }
    private function audit(PDO $pdo,string $accountId,string $action,string $subject): void{$pdo->prepare('INSERT INTO audit_log (id,account_id,action,subject_type,subject_id,occurred_at) VALUES (:id,:account_id,:action,"authentication",:subject_id,UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'action'=>$action,'subject_id'=>$subject]);}
    private static function uuid(): string{$b=random_bytes(16);$b[6]=chr((ord($b[6])&15)|64);$b[8]=chr((ord($b[8])&63)|128);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($b),4));}
}
