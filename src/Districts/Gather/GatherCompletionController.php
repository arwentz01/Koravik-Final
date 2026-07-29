<?php

declare(strict_types=1);

namespace Koravik\Districts\Gather;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;

final class GatherCompletionController
{
    public function __construct(private readonly Database $database) {}
    public function handle(string $method,string $path): bool
    {
        if($method==='GET'&&preg_match('#^/gather/reminders/unsubscribe/([A-Za-z0-9]+)$#',$path,$m)){$this->unsubscribe($m[1]);return true;}
        if($method==='POST'&&preg_match('#^/gather/outcomes/([a-f0-9-]{36})/apply$#',$path,$m)){$this->apply($m[1]);return true;}
        if($method==='GET'&&preg_match('#^/gather/events/([a-f0-9-]{36})/scan$#',$path,$m)){$this->scanner($m[1]);return true;}
        return false;
    }
    private function unsubscribe(string $token):void{try{(new GatherLifecycleService($this->database))->unsubscribe($token);$message='Your reminder has been cancelled.';}catch(RuntimeException $e){$message=$e->getMessage();}$this->page('Reminder preferences','<section class="surface"><h1>'.self::e($message).'</h1><p><a href="https://koravik.com/">Return to Koravik</a></p></section>');}
    private function apply(string $id):void{$this->csrf();$a=Security::requireAccount();try{$reference=(new GatherLifecycleService($this->database))->applyApprovedOutcome((string)$a['id'],$id);$_SESSION['flash']='Approved outcome applied: '.$reference;}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/gather');}
    private function scanner(string $eventId):void
    {
        Security::requireAccount();$id=self::e($eventId);$body='<section class="page-heading"><div><p class="eyebrow">Day-of QR</p><h1>Scan attendee code</h1><p>The camera runs only in a secure HTTPS browser. Manual lookup remains available.</p></div></section><section class="surface"><video id="camera" playsinline muted style="width:100%;max-width:38rem"></video><p id="scan-status" role="status">Camera not started.</p><button class="button" id="start-camera" type="button">Start camera</button><p><a href="/gather/events/'.$id.'/day-of">Use manual lookup</a></p></section><script>const b=document.getElementById("start-camera"),v=document.getElementById("camera"),s=document.getElementById("scan-status");b.addEventListener("click",async()=>{if(!window.isSecureContext){s.textContent="Camera scanning requires HTTPS.";return}if(!navigator.mediaDevices?.getUserMedia||!("BarcodeDetector" in window)){s.textContent="This browser does not support camera QR scanning. Use manual lookup.";return}try{const stream=await navigator.mediaDevices.getUserMedia({video:{facingMode:"environment"},audio:false});v.srcObject=stream;await v.play();const detector=new BarcodeDetector({formats:["qr_code"]});s.textContent="Scanning…";const tick=async()=>{try{const codes=await detector.detect(v);if(codes.length){s.textContent="Code found: "+codes[0].rawValue;stream.getTracks().forEach(t=>t.stop());return}}catch(e){s.textContent="Scan failed. Use manual lookup.";return}requestAnimationFrame(tick)};tick()}catch(e){s.textContent="Camera permission was unavailable. Use manual lookup.";}});</script>';$this->page('QR scanner',$body);
    }
    private function csrf():void{if(!Security::verifyCsrf((string)($_POST['csrf']??'')))throw new RuntimeException('Your session token expired.');}
    private function go(string $to):never{header('Location: '.$to,true,303);exit;}
    private function page(string $title,string $body):void{echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><main class="page">'.$body.'</main></body></html>';}
    private static function e(string $v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
