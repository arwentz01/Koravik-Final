<?php

declare(strict_types=1);

namespace Koravik\Platform\Orientation;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class OrientationService
{
    public function __construct(private readonly Database $database) {}

    public function register(string $displayName,string $email,string $password): array
    {
        $displayName=trim($displayName);
        $email=mb_strtolower(trim($email));
        if($displayName===''||mb_strlen($displayName)>120) throw new RuntimeException('Enter the name you would like Koravik to use.');
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter a valid email address.');
        if(strlen($password)<8) throw new RuntimeException('Use at least 8 characters for your password.');
        $id=self::uuid();
        try {
            $this->database->transaction(function(PDO $pdo) use($id,$displayName,$email,$password): void {
                $pdo->prepare('INSERT INTO platform_accounts (id,email,display_name,role,status,created_at,updated_at) VALUES (:id,:email,:display_name,"user","active",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$id,'email'=>$email,'display_name'=>$displayName]);
                $pdo->prepare('INSERT INTO auth_credentials (account_id,password_hash,updated_at) VALUES (:account_id,:password_hash,UTC_TIMESTAMP())')->execute(['account_id'=>$id,'password_hash'=>password_hash($password,PASSWORD_DEFAULT)]);
                $pdo->prepare('INSERT INTO auth_security_state (account_id,failed_attempts,locked_until,session_version,updated_at) VALUES (:account_id,0,NULL,1,UTC_TIMESTAMP())')->execute(['account_id'=>$id]);
                $pdo->prepare('INSERT INTO account_orientation (account_id,status,created_at,updated_at) VALUES (:account_id,"pending",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['account_id'=>$id]);
                $pdo->prepare('INSERT INTO audit_log (id,account_id,action,subject_type,subject_id,occurred_at) VALUES (:id,:account_id,"account.created","account",:subject_id,UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'account_id'=>$id,'subject_id'=>$id]);
            });
        } catch(\PDOException $exception) {
            if((string)$exception->getCode()==='23000') throw new RuntimeException('An account could not be created with those details.');
            throw $exception;
        }
        return ['id'=>$id,'display_name'=>$displayName,'role'=>'user','session_version'=>1];
    }

    public function pending(string $accountId): bool
    {
        $s=$this->database->pdo()->prepare('SELECT status FROM account_orientation WHERE account_id=:account_id LIMIT 1');
        $s->execute(['account_id'=>$accountId]);
        $status=$s->fetchColumn();
        return $status!==false && $status!=='complete';
    }

    public function complete(string $accountId,string $nextStep): string
    {
        $allowed=['quest'=>'/quests/create','world'=>'/worlds/epic-ordinary','hearth'=>'/hearth'];
        if(!isset($allowed[$nextStep])) throw new RuntimeException('Choose how you would like to begin.');
        $this->database->transaction(function(PDO $pdo) use($accountId,$nextStep): void {
            $pdo->prepare('UPDATE account_orientation SET status="complete",next_step=:next_step,completed_at=COALESCE(completed_at,UTC_TIMESTAMP()),updated_at=UTC_TIMESTAMP() WHERE account_id=:account_id')->execute(['account_id'=>$accountId,'next_step'=>$nextStep]);
            $pdo->prepare('INSERT INTO audit_log (id,account_id,action,subject_type,subject_id,occurred_at) VALUES (:id,:account_id,"orientation.completed","account",:subject_id,UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'subject_id'=>$accountId]);
        });
        return $allowed[$nextStep];
    }

    private static function uuid(): string
    {
        $b=random_bytes(16);$b[6]=chr((ord($b[6])&15)|64);$b[8]=chr((ord($b[8])&63)|128);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($b),4));
    }
}
