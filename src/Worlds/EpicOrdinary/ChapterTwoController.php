<?php

declare(strict_types=1);

namespace Koravik\Worlds\EpicOrdinary;

use Koravik\Platform\Security\Security;
use RuntimeException;

final class ChapterTwoController
{
    public function handle(string $method,string $path): bool
    {
        if(!str_starts_with($path,'/worlds/epic-ordinary/play')) return false;
        Security::startSession();$account=Security::requireAccount();$service=new ChapterTwoService(\database());$accountId=(string)$account['id'];
        try {
            if($method==='GET'&&$path==='/worlds/epic-ordinary/play'){$this->home($service->home($accountId));return true;}
            if($method==='POST'&&$path==='/worlds/epic-ordinary/play/begin'){$this->csrf();$service->begin($accountId);$_SESSION['flash']='The eastern door is open.';$this->redirect('/worlds/epic-ordinary/play');}
            if($method==='POST'&&$path==='/worlds/epic-ordinary/play/choose-refuge'){$this->csrf();$service->chooseRefuge($accountId,(string)($_POST['choice']??''));$_SESSION['flash']='The room remembers what you chose.';$this->redirect('/worlds/epic-ordinary/play');}
        } catch(RuntimeException $e){http_response_code(422);$this->render('Epic Ordinary','<section class="panel state-panel state-error"><h1>The story could not continue.</h1><p>'.self::e($e->getMessage()).'</p><a class="button" href="/worlds/epic-ordinary">Return to World details</a></section>');return true;}
        http_response_code(404);$this->render('World scene unavailable','<section class="panel"><h1>That scene is unavailable.</h1></section>');return true;
    }

    private function home(array $state): void
    {
        $flash=$this->flash();$chapter=(string)$state['current_chapter'];$scene=(string)$state['current_scene'];$support=(string)($state['support_choice_label']??'Not chosen');
        $objective=$state['objective'];$objectiveHtml=$objective?'<section class="world-objective surface"><p class="eyebrow">World objective</p><h2>'.self::e((string)$objective['title']).'</h2><p>'.self::e((string)$objective['description']).'</p><span class="status '.((string)$objective['status']==='completed'?'complete':'').'">'.self::e(ucfirst((string)$objective['status'])).'</span></section>':'';
        $reaction=$state['recent_reaction'];$reactionHtml=$reaction?'<section class="world-reason surface"><p class="eyebrow">Recent change</p><h2>'.self::e((string)$reaction['title']).'</h2><p>'.self::e((string)$reaction['message']).'</p><details><summary>Why did this change?</summary><p>'.self::e((string)$reaction['explanation']).'</p></details></section>':'<section class="world-reason surface muted"><h2>No recent interpreted change</h2><p>The World does not invent activity when no approved fact has arrived.</p></section>';
        $keepsakes='';foreach($state['keepsakes'] as $item)$keepsakes.='<li><strong>'.self::e((string)$item['name']).'</strong><span>'.self::e((string)$item['description']).'</span></li>';
        $history='';foreach($state['relationship_history'] as $item)$history.='<li><strong>'.((int)$item['delta_value']>=0?'+':'').(int)$item['delta_value'].'</strong> '.self::e((string)$item['explanation']).'</li>';
        if(!$state['support_choice']){
            $sceneHtml='<p class="eyebrow">Chapter One</p><h1>The Caretaker is waiting.</h1><p>Choose how you want support before the eastern room can open.</p><a class="button" href="/world/epic-ordinary">Choose a support style</a>';
        } elseif($chapter!=='the-eastern-room'){
            $tone=match((string)$state['support_choice']){'direct'=>'“There is another room. We can open it without pretending the rest of the house is finished.”','quiet'=>'“I kept the eastern key nearby. I did not use it without you.”',default=>'“There is another room when you are ready. Opening one door is enough.”'};
            $sceneHtml='<p class="eyebrow">Chapter Two · The Eastern Room</p><h1>A line of light beneath the door.</h1><blockquote>'.self::e($tone).'</blockquote><p>The Caretaker asks what deserves tending, not what you failed to finish.</p><section class="eastern-room-preview" aria-label="Eastern Room preview"><span aria-hidden="true">✦</span><div><h2>The door is unlocked by consent, not productivity.</h2><p>Opening it changes fictional World State only. It does not create, complete, or edit a Quest.</p></div></section><form method="post" action="/worlds/epic-ordinary/play/begin">'.$this->csrfField().'<button class="button" type="submit">Open the eastern door</button></form>';
        } elseif($scene==='doorway'){
            $sceneHtml='<p class="eyebrow">Chapter Two · The Eastern Room</p><h1>The room is empty, but not accusing.</h1><blockquote>“A refuge does not have to prove anything. What should this place make possible?”</blockquote><form method="post" action="/worlds/epic-ordinary/play/choose-refuge">'.$this->csrfField().'<div class="choice-list refuge-choice-list" aria-label="Choose the Eastern Room purpose"><button name="choice" value="rest"><strong>A room for rest</strong><span>A place that asks nothing before offering shelter.</span></button><button name="choice" value="making"><strong>A room for making</strong><span>A place where unfinished work can remain welcome.</span></button><button name="choice" value="welcome"><strong>A room for welcome</strong><span>A place prepared for connection without obligation.</span></button></div></form><section class="world-boundary-note"><h2>What this choice changes</h2><p>It completes one World objective, records one fictional choice, creates one keepsake, and adds one Caretaker relationship moment. It does not touch Quests or Chronicle.</p></section>';
        } else {
            $choice=$this->chosenLabel((string)$state['installation_id']);
            $sceneHtml='<p class="eyebrow">Chapter Two · The Eastern Room</p><h1>The room has taken its first shape.</h1><blockquote>“'.self::e($choice).' It does not need to become everything today.”</blockquote><p>The choice is preserved as fictional World State. It does not create or alter a real-life Quest.</p><div class="local-actions"><a class="button" href="/home/rooms/eastern_room">Visit the Eastern Room</a><a class="button secondary" href="/worlds/epic-ordinary/progress">View World State</a><a class="button secondary" href="/hearth">Return to Hearth</a></div>';
        }
        $permission=(int)($state['quest_fact_granted']??0)===1?'Quest completion facts are permitted.':'Quest completion facts are revoked; the story continues without them.';
        $body=$flash.'<div class="world-home"><section class="world-scene surface">'.$sceneHtml.'</section><aside class="world-sidebar">'.$objectiveHtml.$reactionHtml.'<section class="surface world-summary"><p class="eyebrow">Caretaker relationship</p><h2>'.self::e(ucfirst((string)$state['relationship_stage'])).' · '.(int)$state['trust_score'].' trust</h2><p>Support style: '.self::e($support).'</p><p>'.self::e($permission).'</p><a href="/privacy">Review permissions</a></section><section class="surface world-summary"><h2>Keepsakes</h2>'.($keepsakes?'<ul class="keepsake-list">'.$keepsakes.'</ul>':'<p>No keepsakes yet.</p>').'</section></aside></div><section class="surface world-history"><h2>Why this relationship changed</h2>'.($history?'<ol>'.$history.'</ol>':'<p>The relationship is new.</p>').'</section>';
        $this->render('Epic Ordinary',$body);
    }

    private function chosenLabel(string $installationId): string
    {
        $s=\database()->pdo()->prepare('SELECT choice_label FROM world_choice_history WHERE installation_id=:installation_id AND scene_key="eastern-room-purpose" LIMIT 1');$s->execute(['installation_id'=>$installationId]);return (string)($s->fetchColumn()?:'The room remains open');
    }
    private function render(string $title,string $body): void{echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="light dark"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"><link rel="stylesheet" href="/assets/world.css"></head><body><main id="main" class="page">'.$body.'</main></body></html>';}
    private function csrf():void{if(!Security::verifyCsrf((string)($_POST['csrf']??'')))throw new RuntimeException('Your session changed. Please try again.');}
    private function csrfField():string{return '<input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'">';}
    private function flash():string{$v=(string)($_SESSION['flash']??'');unset($_SESSION['flash']);return $v?'<div class="notice" role="status">'.self::e($v).'</div>':'';}
    private function redirect(string $to):never{header('Location: '.$to,true,303);exit;}
    private static function e(string $v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
