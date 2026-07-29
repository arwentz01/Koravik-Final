<?php

declare(strict_types=1);

namespace Koravik\Platform\Mail;

use RuntimeException;

final class SmtpMailer
{
    public function send(array $message): string
    {
        $host=(string)getenv('MAIL_HOST');$port=(int)(getenv('MAIL_PORT')?:465);$user=(string)getenv('MAIL_USERNAME');$pass=(string)getenv('MAIL_PASSWORD');
        $from=(string)(getenv('MAIL_FROM_ADDRESS')?:$user);$fromName=(string)(getenv('MAIL_FROM_NAME')?:'Koravik');$encryption=(string)(getenv('MAIL_ENCRYPTION')?:'ssl');
        if($host===''||$user===''||$pass===''||$from==='')throw new RuntimeException('Mail transport is not configured.');
        $target=($encryption==='ssl'?'ssl://':'').$host.':'.$port;$socket=@stream_socket_client($target,$errno,$error,15,STREAM_CLIENT_CONNECT);
        if(!$socket)throw new RuntimeException('SMTP connection failed: '.$error);
        stream_set_timeout($socket,15);$this->expect($socket,[220]);$this->command($socket,'EHLO koravik.local',[250]);
        if($encryption==='tls'){$this->command($socket,'STARTTLS',[220]);if(!stream_socket_enable_crypto($socket,true,STREAM_CRYPTO_METHOD_TLS_CLIENT))throw new RuntimeException('SMTP TLS negotiation failed.');$this->command($socket,'EHLO koravik.local',[250]);}
        $this->command($socket,'AUTH LOGIN',[334]);$this->command($socket,base64_encode($user),[334]);$this->command($socket,base64_encode($pass),[235]);
        $to=(string)$message['recipient_email'];$this->command($socket,'MAIL FROM:<'.$from.'>',[250]);$this->command($socket,'RCPT TO:<'.$to.'>',[250,251]);$this->command($socket,'DATA',[354]);
        $headers=['From: '.$this->address($fromName,$from),'To: '.$this->address((string)($message['recipient_name']??''),$to),'Subject: '.$this->encode((string)$message['subject']),'Date: '.gmdate(DATE_RFC2822),'Message-ID: <'.bin2hex(random_bytes(12)).'@'.preg_replace('/^mail\./','',$host).'>','MIME-Version: 1.0','Content-Type: multipart/alternative; boundary="koravik-boundary"'];
        if(!empty($message['reply_to_email']))$headers[]='Reply-To: '.$this->address((string)($message['reply_to_name']??''),(string)$message['reply_to_email']);
        $body=implode("\r\n",$headers)."\r\n\r\n--koravik-boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\n".quoted_printable_encode((string)$message['text_body'])."\r\n--koravik-boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\n".quoted_printable_encode((string)$message['html_body'])."\r\n--koravik-boundary--\r\n.";
        fwrite($socket,$body."\r\n");$this->expect($socket,[250]);$this->command($socket,'QUIT',[221]);fclose($socket);return 'smtp:'.bin2hex(random_bytes(8));
    }

    private function command($socket,string $command,array $codes):void{fwrite($socket,$command."\r\n");$this->expect($socket,$codes);}
    private function expect($socket,array $codes):void{$response='';do{$line=fgets($socket,515);if($line===false)throw new RuntimeException('SMTP connection closed unexpectedly.');$response.=$line;}while(isset($line[3])&&$line[3]==='-');$code=(int)substr($line,0,3);if(!in_array($code,$codes,true))throw new RuntimeException('SMTP rejected request: '.trim($response));}
    private function address(string $name,string $email):string{return $name===''?'<'.$email.'>':$this->encode($name).' <'.$email.'>';}
    private function encode(string $value):string{return '=?UTF-8?B?'.base64_encode($value).'?=';}
}
