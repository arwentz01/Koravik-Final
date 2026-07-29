<?php

declare(strict_types=1);

namespace Koravik\Platform\Mail;

use Koravik\Platform\Database\Database;

final class MailQueue
{
    public function __construct(private readonly Database $database) {}

    public function enqueue(string $type,string $email,string $name,string $subject,string $html,string $text,?string $replyTo=null,?string $replyName=null): string
    {
        $id=self::uuid();
        $this->database->pdo()->prepare('INSERT INTO platform_mail_deliveries (id,message_type,recipient_email,recipient_name,reply_to_email,reply_to_name,subject,html_body,text_body,status,attempts,available_at,created_at,updated_at) VALUES (:id,:type,:email,:name,:reply_to,:reply_name,:subject,:html,:text,"pending",0,UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute([
            'id'=>$id,'type'=>$type,'email'=>strtolower(trim($email)),'name'=>trim($name),'reply_to'=>$replyTo,'reply_name'=>$replyName,'subject'=>$subject,'html'=>$html,'text'=>$text
        ]);
        return $id;
    }

    private static function uuid():string{$d=random_bytes(16);$d[6]=chr((ord($d[6])&0x0f)|0x40);$d[8]=chr((ord($d[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4));}
}
