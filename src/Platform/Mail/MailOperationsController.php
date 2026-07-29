<?php

declare(strict_types=1);

namespace Koravik\Platform\Mail;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;

final class MailOperationsController
{
    public function __construct(private readonly Database $database) {}

    public function handle(string $method,string $path): bool
    {
        if(!str_starts_with($path,'/system/mail'))return false;
        $account=Security::requireAccount();
        if(!in_array((string)($account['role']??''),['owner','admin'],true)){$this->forbidden();return true;}
        $service=new MailOperationsService($this->database);
        try {
            if($method==='GET'&&$path==='/system/mail'){$this->index($service);return true;}
            if($method==='GET'&&preg_match('#^/system/mail/([a-f0-9-]{36})$#',$path,$m)){$this->show($service,$m[1]);return true;}
            if($method==='POST'&&$path==='/system/mail/test'){$this->csrf();$id=$service->enqueueTest((string)$account['id']);$this->flash('Test delivery queued: '.$id);$this->redirect('/system/mail');}
            if($method==='POST'&&$path==='/system/mail/recover'){$this->csrf();$count=$service->recoverStale();$this->flash($count.' stale processing claim(s) recovered.');$this->redirect('/system/mail');}
            if($method==='POST'&&preg_match('#^/system/mail/([a-f0-9-]{36})/(retry|cancel|resend)$#',$path,$m)){
                $this->csrf();
                if($m[2]==='retry')$service->retry($m[1]);
                elseif($m[2]==='cancel')$service->cancel($m[1],(string)$account['id']);
                else{$newId=$service->resend($m[1]);$this->flash('Replacement delivery queued: '.$newId);$this->redirect('/system/mail/'.$m[1]);}
                $this->flash(ucfirst($m[2]).' request accepted.');$this->redirect('/system/mail/'.$m[1]);
            }
        } catch(RuntimeException $e){$this->flash($e->getMessage());$this->redirect('/system/mail');}
        return false;
    }

    private function index(MailOperationsService $service): void
    {
        $summary=$service->summary();$cards='';
        foreach(['pending','processing','retry','failed','sent','cancelled','stale'] as $status)$cards.='<article class="metric-card"><p class="eyebrow">'.self::e($status).'</p><strong>'.(int)$summary[$status].'</strong></article>';
        $rows='';
        foreach($service->recent() as $row){
            $safe=$this->redactEmail((string)$row['recipient_email']);
            $rows.='<tr><td><a href="/system/mail/'.self::e((string)$row['id']).'">'.self::e((string)$row['subject']).'</a><br><small>'.self::e((string)$row['message_type']).'</small></td><td>'.self::e($safe).'</td><td>'.self::e((string)$row['status']).'</td><td>'.(int)$row['attempts'].'</td><td>'.self::e((string)$row['created_at']).' UTC</td></tr>';
        }
        $body=$this->flashHtml().'<section class="page-heading"><div><p class="eyebrow">System operations</p><h1>Platform Mail</h1><p>Review queue health, inspect redacted diagnostics, and perform bounded recovery actions without changing message history.</p></div></section><section class="metric-grid">'.$cards.'</section><section class="settings-card"><h2>Operations</h2><div class="local-actions"><form method="post" action="/system/mail/test">'.$this->csrfField().'<button class="button" type="submit">Queue test delivery</button></form><form method="post" action="/system/mail/recover">'.$this->csrfField().'<button class="button secondary" type="submit">Recover stale claims</button></form></div></section><section class="settings-card"><h2>Recent deliveries</h2><div class="table-scroll"><table><thead><tr><th>Message</th><th>Recipient</th><th>Status</th><th>Attempts</th><th>Created</th></tr></thead><tbody>'.($rows?:'<tr><td colspan="5">No deliveries yet.</td></tr>').'</tbody></table></div></section>';
        $this->render('Platform Mail',$body);
    }

    private function show(MailOperationsService $service,string $id): void
    {
        $row=$service->delivery($id);$status=(string)$row['status'];$actions='';
        if(in_array($status,['failed','retry'],true))$actions.=$this->action($id,'retry','Retry now');
        if(in_array($status,['pending','retry','failed'],true))$actions.=$this->action($id,'cancel','Cancel delivery','secondary');
        if(in_array($status,['sent','failed','cancelled'],true))$actions.=$this->action($id,'resend','Create resend');
        $diagnostic=(string)$row['safe_failure_reason'];
        $body=$this->flashHtml().'<p><a href="/system/mail">← Back to Platform Mail</a></p><section class="page-heading"><div><p class="eyebrow">Delivery '.self::e($status).'</p><h1>'.self::e((string)$row['subject']).'</h1><p>Recipient: '.self::e((string)$row['safe_recipient']).'</p></div></section><section class="settings-card"><dl><div><dt>Message type</dt><dd>'.self::e((string)$row['message_type']).'</dd></div><div><dt>Attempts</dt><dd>'.(int)$row['attempts'].'</dd></div><div><dt>Created</dt><dd>'.self::e((string)$row['created_at']).' UTC</dd></div><div><dt>Available</dt><dd>'.self::e((string)$row['available_at']).' UTC</dd></div><div><dt>Sent</dt><dd>'.self::e((string)($row['sent_at']??'Not sent')).'</dd></div><div><dt>Provider reference</dt><dd>'.self::e((string)($row['provider_reference']??'None')).'</dd></div><div><dt>Original delivery</dt><dd>'.self::e((string)($row['resend_of_id']??'Original')).'</dd></div></dl>'.($diagnostic!==''?'<h2>Redacted diagnostic</h2><pre>'.self::e($diagnostic).'</pre>':'').'<div class="local-actions">'.$actions.'</div></section>';
        $this->render('Mail delivery',$body);
    }

    private function action(string $id,string $action,string $label,string $class=''): string{return '<form method="post" action="/system/mail/'.self::e($id).'/'.$action.'">'.$this->csrfField().'<button class="button '.$class.'" type="submit">'.self::e($label).'</button></form>';}
    private function forbidden(): void{http_response_code(403);$this->render('Access denied','<section class="empty-state"><h1>Access denied</h1><p>Platform Mail operations require an Owner or Admin role.</p></section>');}
    private function render(string $title,string $body): void{echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><main id="main" class="page">'.$body.'</main></body></html>';}
    private function csrf(): void{if(!Security::verifyCsrf(isset($_POST['csrf'])?(string)$_POST['csrf']:null))throw new RuntimeException('Your session changed. Please try again.');}
    private function csrfField(): string{return '<input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'">';}
    private function flash(string $message): void{$_SESSION['flash']=$message;}
    private function flashHtml(): string{$message=(string)($_SESSION['flash']??'');unset($_SESSION['flash']);return $message!==''?'<div class="notice" role="status">'.self::e($message).'</div>':'';}
    private function redirect(string $location): never{header('Location: '.$location,true,303);exit;}
    private function redactEmail(string $email): string{[$local,$domain]=array_pad(explode('@',$email,2),2,'');return $domain===''?'[invalid address]':mb_substr($local,0,1).'***@'.$domain;}
    private static function e(string $value): string{return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
