<?php

declare(strict_types=1);

namespace Koravik\Platform\Journey;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;

final class JourneyArcController
{
    public function __construct(private readonly Database $database) {}

    public function handle(string $method,string $path): bool
    {
        if ($method==='GET' && $path==='/journey') { $this->index(); return true; }
        if ($method==='POST' && preg_match('#^/journey/invitations/([a-f0-9-]{36})/(accept|decline|snooze)$#',$path,$m)) { $this->invitation($m[1],$m[2]); return true; }
        if ($method==='POST' && $path==='/journey/caretaker/converse') { $this->converse(); return true; }
        if ($method==='POST' && $path==='/journey/source-proposals') { $this->createProposal(); return true; }
        if ($method==='POST' && preg_match('#^/journey/source-proposals/([a-f0-9-]{36})/(accept|decline)$#',$path,$m)) { $this->proposal($m[1],$m[2]); return true; }
        return false;
    }

    private function index(): void
    {
        $account=Security::requireAccount();$data=(new JourneyArcService($this->database))->dashboard((string)$account['id']);
        $inv='';foreach($data['invitations'] as $i){$actions=(string)$i['status']==='open'||(string)$i['status']==='snoozed'?'<div class="inline-actions">'.$this->post('/journey/invitations/'.$i['id'].'/accept','Accept and create Quest','button').$this->post('/journey/invitations/'.$i['id'].'/snooze','Snooze','quiet-button').$this->post('/journey/invitations/'.$i['id'].'/decline','Decline','quiet-button').'</div>':'<p class="status">'.self::e(ucwords(str_replace('_',' ',(string)$i['status']))).'</p>';$inv.='<article class="surface journey-card"><p class="eyebrow">Epic Ordinary invitation</p><h2>'.self::e((string)$i['title']).'</h2><p>'.self::e((string)$i['body']).'</p>'.$actions.'</article>';}
        $conv='';foreach($data['conversations'] as $c){$conv.='<article class="memory-card"><p>'.self::e((string)$c['character_response']).'</p><small>'.self::e((string)$c['created_at']).' UTC</small></article>';}
        $props='';foreach($data['proposals'] as $p){$actions=(string)$p['status']==='open'?'<div class="inline-actions">'.$this->post('/journey/source-proposals/'.$p['id'].'/accept','Create Quest','button').$this->post('/journey/source-proposals/'.$p['id'].'/decline','Decline','quiet-button').'</div>':'<p class="status">'.self::e(ucfirst((string)$p['status'])).'</p>';$props.='<article class="surface journey-card"><p class="eyebrow">'.self::e(ucfirst((string)$p['source_domain'])).' proposal</p><h2>'.self::e((string)$p['title']).'</h2><p>'.self::e((string)$p['purpose']).'</p>'.$actions.'</article>';}
        $keeps='';foreach($data['keepsakes'] as $k){$keeps.='<li><strong>'.self::e((string)$k['label']).'</strong><span>'.self::e(ucwords(str_replace('_',' ',(string)$k['room_key']))).'</span></li>';}
        $body='<section class="page-heading"><div><p class="eyebrow">Journey</p><h1>The World may invite. You still decide.</h1><p>Story, events, and community can suggest meaningful action without silently creating obligations.</p></div><a class="button secondary" href="/home">Return Home</a></section><section><h2>Story invitations</h2><div class="grid">'.($inv?:'<article class="empty-state"><h3>No invitations are waiting.</h3></article>').'</div></section><section class="surface conversation-panel"><p class="eyebrow">By the fire</p><h2>Speak with the Caretaker</h2><p>Choose the kind of moment you need. There is no correct dialogue path.</p><form method="post" action="/journey/caretaker/converse"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><div class="resolution-grid"><button class="button secondary" name="choice" value="gratitude">Share gratitude</button><button class="button secondary" name="choice" value="repair">Ask to repair</button><button class="button secondary" name="choice" value="disagree">Disagree honestly</button><button class="button secondary" name="choice" value="quiet">Sit quietly</button></div></form><div class="conversation-history">'.$conv.'</div></section><section><div class="section-heading"><h2>Supporting-domain proposals</h2></div><div class="grid">'.($props?:'<article class="empty-state"><h3>No Beacon or Gather proposals are waiting.</h3></article>').'</div><details class="surface proposal-demo"><summary>Create a bounded integration proposal</summary><form method="post" action="/journey/source-proposals"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Source<select name="domain"><option value="beacon">Beacon event</option><option value="gather">Gather collaboration</option></select></label><label>Reference<input name="reference" maxlength="180" required placeholder="event or collaboration reference"></label><label>Quest suggestion<input name="title" maxlength="180" required></label><label>Pattern<select name="kind"><option value="prepare">Prepare</option><option value="attend">Attend</option><option value="volunteer">Volunteer</option><option value="follow_up">Follow up</option><option value="contribute">Contribute</option></select></label><button class="button" type="submit">Create proposal</button></form></details></section><section class="surface keepsake-shelf"><p class="eyebrow">Home keepsakes</p><h2>Placed around the house</h2><ul>'.($keeps?:'<li>No keepsakes have been placed yet.</li>').'</ul></section>';
        echo $this->page($body);
    }

    private function invitation(string $id,string $decision): void { $this->csrf();$a=Security::requireAccount();try{$q=(new JourneyArcService($this->database))->decideInvitation((string)$a['id'],$id,$decision);$_SESSION['flash']=$q?'Invitation accepted. The Quest remains yours to shape.':'Invitation updated without penalty.';$this->redirect($q?'/quests/'.$q:'/journey');}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();$this->redirect('/journey');} }
    private function converse(): void { $this->csrf();$a=Security::requireAccount();try{(new JourneyArcService($this->database))->converse((string)$a['id'],(string)($_POST['choice']??''));$_SESSION['flash']='The moment was remembered.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->redirect('/journey'); }
    private function createProposal(): void { $this->csrf();$a=Security::requireAccount();try{(new JourneyArcService($this->database))->createSourceProposal((string)$a['id'],(string)($_POST['domain']??''),(string)($_POST['reference']??''),(string)($_POST['kind']??''),(string)($_POST['title']??''));$_SESSION['flash']='Proposal created. It is not a Quest until accepted.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->redirect('/journey'); }
    private function proposal(string $id,string $decision): void { $this->csrf();$a=Security::requireAccount();try{$q=(new JourneyArcService($this->database))->decideProposal((string)$a['id'],$id,$decision);$_SESSION['flash']=$q?'Proposal accepted and converted with provenance.':'Proposal declined without consequence.';$this->redirect($q?'/quests/'.$q:'/journey');}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();$this->redirect('/journey');} }
    private function post(string $action,string $label,string $class): string { return '<form method="post" action="'.self::e($action).'"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><button class="'.self::e($class).'" type="submit">'.self::e($label).'</button></form>'; }
    private function csrf(): void { if(!Security::verifyCsrf((string)($_POST['csrf']??''))) throw new RuntimeException('Your session token expired. Please try again.'); }
    private function redirect(string $to): never { header('Location: '.$to, true, 303); exit; }
    private function page(string $body): string { $flash=isset($_SESSION['flash'])?'<div class="notice">'.self::e((string)array_shift($_SESSION)).'</div>':'';return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Journey · Koravik</title><link rel="stylesheet" href="/assets/app.css"><link rel="stylesheet" href="/assets/journey.css"></head><body><main id="main" class="page">'.$flash.$body.'</main><footer>Koravik · Invitation without coercion.</footer></body></html>'; }
    private static function e(string $v): string { return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
}