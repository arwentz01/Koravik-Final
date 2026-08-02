<?php

declare(strict_types=1);

namespace Koravik\Districts\Quests;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;

final class LivingQuestController
{
    public function __construct(private readonly Database $database)
    {
    }

    public function handle(string $method, string $path): bool
    {
        $account=Security::account();if(!$account)return false;$accountId=(string)$account['id'];$service=new QuestService($this->database);
        if($method==='GET'&&$path==='/quests/manage'){$this->management($service->management($accountId));return true;}
        if($method==='GET'&&preg_match('#^/quests/([a-f0-9-]{36})/edit$#',$path,$m)){$quest=$service->getForAccount($m[1],$accountId);if(!$quest){http_response_code(404);$this->page('Quest unavailable','<section class="panel"><h1>That Quest is unavailable.</h1></section>');return true;}$this->edit($quest);return true;}
        if($method==='POST'&&preg_match('#^/quests/([a-f0-9-]{36})/edit$#',$path,$m)){$this->requireCsrf();try{$service->updateDetails($m[1],$accountId,$_POST);$_SESSION['flash']='Quest details updated.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->redirect('/quests/'.$m[1].'/edit');}
        if($method==='GET'&&preg_match('#^/quests/([a-f0-9-]{36})/recurrence$#',$path,$m)){$quest=$service->getForAccount($m[1],$accountId);if(!$quest){http_response_code(404);$this->page('Quest unavailable','<section class="panel"><h1>That Quest is unavailable.</h1></section>');return true;}$this->recurrence($quest);return true;}
        if($method==='POST'&&preg_match('#^/quests/([a-f0-9-]{36})/recurrence$#',$path,$m)){$this->requireCsrf();try{$service->updateRecurrence($m[1],$accountId,$_POST);$_SESSION['flash']='Quest recurrence updated.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->redirect('/quests/'.$m[1].'/recurrence');}
        if($method==='POST'&&preg_match('#^/quests/([a-f0-9-]{36})/reschedule$#',$path,$m)){$this->requireCsrf();try{$service->rescheduleNext($m[1],$accountId,(string)($_POST['scheduled_for']??''));$_SESSION['flash']='Next occurrence rescheduled.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->redirect('/quests/'.$m[1]);}
        if($method==='GET'&&preg_match('#^/quests/([a-f0-9-]{36})/history$#',$path,$m)){$this->history($service->history($m[1],$accountId));return true;}
        if($method==='GET'&&preg_match('#^/quests/([a-f0-9-]{36})/timeline$#',$path,$m)){$this->timeline($service->timelineFor($m[1],$accountId));return true;}
        if ($method === 'POST' && preg_match('#^/quests/([a-f0-9-]{36})/focus$#', $path, $matches)) {
            $this->requireCsrf();
            $account = Security::requireAccount();
            try {
                (new QuestService($this->database))->updateFocus($matches[1], (string) $account['id'], (string) ($_POST['purpose'] ?? ''), (string) ($_POST['next_step'] ?? ''));
                $_SESSION['flash'] = 'Quest focus updated.';
            } catch (RuntimeException $exception) {
                $_SESSION['flash'] = $exception->getMessage();
            }
            $this->redirect('/quests/' . $matches[1]);
        }

        if ($method === 'POST' && preg_match('#^/quests/([a-f0-9-]{36})/resolve$#', $path, $matches)) {
            $this->requireCsrf();
            $account = Security::requireAccount();
            try {
                (new QuestService($this->database))->resolve($matches[1], (string) $account['id'], (string) ($_POST['outcome'] ?? ''), (string) ($_POST['reflection'] ?? ''));
                $_SESSION['flash'] = 'The Quest outcome was recorded without erasing the journey.';
            } catch (RuntimeException $exception) {
                $_SESSION['flash'] = $exception->getMessage();
            }
            $this->redirect('/quests/' . $matches[1]);
        }

        return false;
    }

    private function management(array $groups):void{$sections='';foreach(['active'=>'Active','paused'=>'Paused','archived'=>'Archived'] as $key=>$label){$cards='';foreach($groups[$key] as $q)$cards.='<article class="quest-manage-card"><div><p class="eyebrow">'.self::e(QuestService::typeLabel((string)$q['quest_type'])).'</p><h3>'.self::e((string)$q['title']).'</h3><p>'.($q['next_scheduled_for']?'Next: '.self::e((string)$q['next_scheduled_for']):'No occurrence waiting').' · '.(int)$q['completion_count'].' completed</p></div><p class="local-actions"><a href="/quests/'.self::e((string)$q['id']).'">Open</a><a href="/quests/'.self::e((string)$q['id']).'/edit">Edit</a><a href="/quests/'.self::e((string)$q['id']).'/recurrence">Recurrence</a><a href="/quests/'.self::e((string)$q['id']).'/history">History</a></p></article>';$sections.='<section><div class="section-heading"><h2>'.$label.'</h2><span>'.count($groups[$key]).'</span></div><div class="quest-management-grid">'.($cards?:'<p class="empty-state">No '.strtolower($label).' Quests.</p>').'</div></section>';}$this->page('Manage Quests','<section class="page-heading"><div><p class="eyebrow">Quest management · Quest Momentum Dashboard</p><h1>Every commitment has a clear state.</h1><p>Edit, reschedule, pause, restore, recurrence, or review history without losing the record of what happened. source-originated commitments, paused work, completed evidence, and next honest actions stay warm without becoming productivity pressure.</p></div><a class="button" href="/quests/create">New Quest</a></section>'.$sections);}
    private function edit(array $q):void{$flash=$this->flash();$id=self::e((string)$q['id']);$this->page('Edit Quest',$flash.'<section class="page-heading"><div><p class="eyebrow">Edit Quest</p><h1>'.self::e((string)$q['title']).'</h1><p>Editing changes the Quest-owned record. It does not rewrite completed occurrence history.</p></div><a href="/quests/'.$id.'">Cancel</a></section><form class="panel" method="post" action="/quests/'.$id.'/edit">'.$this->csrfField().'<label>Title<input name="title" maxlength="180" required value="'.self::e((string)$q['title']).'"></label><label>Notes<textarea name="description" maxlength="4000">'.self::e((string)$q['description']).'</textarea></label><label>Why this matters<textarea name="purpose" maxlength="2000">'.self::e((string)($q['purpose']??'')).'</textarea></label><label>Next step<input name="next_step" maxlength="180" value="'.self::e((string)($q['next_step']??'')).'"></label><button class="button">Save Quest details</button></form>');}
    private function recurrence(array $q):void{$id=self::e((string)$q['id']);$frequency=(string)($q['frequency']??'none');$weekdays=array_filter(explode(',',(string)($q['weekdays']??'')));$option=fn(string $v,string $l):string=>'<option value="'.$v.'"'.($frequency===$v?' selected':'').'>'.$l.'</option>';$days='';foreach([1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun'] as $n=>$label)$days.='<label class="check-row"><input type="checkbox" name="weekdays[]" value="'.$n.'"'.(in_array((string)$n,$weekdays,true)?' checked':'').'> '.$label.'</label>';$preview=QuestService::recurrenceLabel($q)?:'One-time Quest';$this->page('Quest recurrence',$this->flash().'<section class="page-heading recurrence-editor"><div><p class="eyebrow">Quest recurrence editor</p><h1>'.self::e((string)$q['title']).'</h1><p>Change when this intention recurs. Completing an occurrence is still separate from creating future occurrences.</p></div><a href="/quests/'.$id.'">Back to Quest</a></section><section class="surface"><h2>Plain-language preview</h2><p class="schedule-label">'.self::e($preview).'</p><p class="meta">Timezone: '.self::e((string)($q['timezone']??'America/New_York')).'</p></section><form class="panel" method="post" action="/quests/'.$id.'/recurrence">'.$this->csrfField().'<label>Repeat<select name="frequency">'.$option('none','One time').$option('daily','Daily').$option('weekly','Weekly').$option('monthly','Monthly').$option('yearly','Yearly').'</select></label><label>Every<input name="interval_count" type="number" min="1" max="365" value="'.self::e((string)($q['interval_count']??'1')).'"></label><label>Starts on<input name="starts_on" type="date" value="'.self::e((string)($q['starts_on']??gmdate('Y-m-d'))).'"></label><label>Ends on<input name="ends_on" type="date" value="'.self::e((string)($q['ends_on']??'')).'"></label><fieldset><legend>Weekly days</legend>'.$days.'</fieldset><label>Timezone<input name="timezone" value="'.self::e((string)($q['timezone']??'America/New_York')).'"></label><button class="button">Apply recurrence</button></form>');}
    private function history(array $data):void{$items='';foreach($data['occurrences'] as $o)$items.='<li><strong>'.self::e(ucfirst((string)$o['status'])).'</strong><span>'.self::e((string)$o['scheduled_for']).($o['rescheduled_from']?' · moved from '.self::e((string)$o['rescheduled_from']):'').'</span></li>';$q=$data['quest'];$this->page('Quest history','<section class="page-heading"><div><p class="eyebrow">Quest history</p><h1>'.self::e((string)$q['title']).'</h1><p>Occurrence history is read-only evidence of scheduling and completion.</p></div><a href="/quests/'.self::e((string)$q['id']).'">Back to Quest</a></section><section class="panel"><ol class="quest-history">'.($items?:'<li>No occurrence history yet.</li>').'</ol></section>');}
    private function timeline(array $data):void{$q=$data['quest'];$events='';foreach($data['events'] as $e)$events.='<li><strong>'.self::e(str_replace('_',' ',(string)$e['event_type'])).'</strong><span>'.self::e((string)$e['summary']).'</span><small>'.self::e((string)$e['occurred_at']).' UTC</small></li>';foreach($data['occurrences'] as $o)$events.='<li><strong>Occurrence '.self::e((string)$o['status']).'</strong><span>Scheduled '.self::e((string)$o['scheduled_for']).($o['rescheduled_from']?' · moved from '.self::e((string)$o['rescheduled_from']):'').'</span><small>'.self::e((string)$o['updated_at']).' UTC</small></li>';$this->page('Quest timeline','<section class="page-heading quest-detail-timeline"><div><p class="eyebrow">Quest detail timeline</p><h1>'.self::e((string)$q['title']).'</h1><p>Creation, edits, recurrence changes, completions, reversals, pauses, resumes, and reflections stay source-owned and inspectable.</p></div><a href="/quests/'.self::e((string)$q['id']).'">Back to Quest</a></section><section class="panel"><ol class="quest-history">'.($events?:'<li>No timeline events yet.</li>').'</ol></section>');}
    private function page(string $title,string $body):void{echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"><link rel="stylesheet" href="/assets/quest-management.css"></head><body><main id="main" class="page">'.$body.'</main></body></html>';}
    private function flash():string{$f=(string)($_SESSION['flash']??'');unset($_SESSION['flash']);return $f?'<div class="notice" role="status">'.self::e($f).'</div>':'';}
    private function csrfField():string{return '<input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'">';}
    private static function e(string $v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}

    private function requireCsrf(): void
    {
        if (!hash_equals(Security::csrfToken(), (string) ($_POST['csrf'] ?? ''))) {
            throw new RuntimeException('Your session changed. Please try again.');
        }
    }

    private function redirect(string $path): never
    {
        header('Location: ' . $path, true, 303);
        exit;
    }
}
