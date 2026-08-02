<?php

declare(strict_types=1);

namespace Koravik\Platform\Orientation;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;

final class OrientationController
{
    private readonly OrientationService $service;
    public function __construct(Database $database){$this->service=new OrientationService($database);}

    public function handle(string $method,string $path): bool
    {
        if($method==='GET'&&$path==='/register'){$this->registerPage();return true;}
        if($method==='POST'&&$path==='/register'){$this->register();return true;}
        $account=Security::account();
        if($account&&$method==='GET'&&$path==='/hearth'&&$this->service->pending((string)$account['id'])){$this->redirect('/onboarding');}
        if($method==='GET'&&$path==='/onboarding'){$this->onboarding();return true;}
        if($method==='POST'&&$path==='/onboarding/complete'){$this->complete();return true;}
        return false;
    }

    private function registerPage(?string $error=null): void
    {
        if(Security::account())$this->redirect('/hearth');
        $notice=$error?'<div class="notice error" role="alert">'.self::e($error).'</div>':'';
        $this->render('Create account','<section class="auth-card"><p class="eyebrow">Begin gently</p><h1>Create your Koravik account.</h1><p>Koravik helps you act with intention and remember what mattered. Worlds may reflect approved facts, but they never replace or rewrite real life.</p>'.$notice.'<form method="post" action="/register">'.$this->csrfField().'<label>Name<input name="display_name" maxlength="120" autocomplete="name" required></label><label>Email<input type="email" name="email" autocomplete="email" required></label><label>Password<input type="password" name="password" minlength="8" autocomplete="new-password" required></label><button class="button" type="submit">Create account</button></form><p><a href="/login">Already have an account?</a></p></section>');
    }

    private function register(): void
    {
        $this->csrf();
        try{$account=$this->service->register((string)($_POST['display_name']??''),(string)($_POST['email']??''),(string)($_POST['password']??''));Security::establishAccount($account);$this->redirect('/onboarding');}
        catch(RuntimeException $e){http_response_code(422);$this->registerPage($e->getMessage());}
    }

    private function onboarding(): void
    {
        $account=Security::requireAccount();
        if(!$this->service->pending((string)$account['id']))$this->redirect('/hearth');
        $body='<section class="page-heading first-run-guided-setup"><div><p class="eyebrow">First-Run Guided Setup</p><h1>Your life stays real. Koravik helps you notice its meaning.</h1><p>Quests hold intentions and actions. Chronicle preserves moments worth remembering. Worlds may interpret approved, minimized facts into fiction. Companion can suggest, but you remain in charge.</p></div></section><section class="surface"><h2>How would you like to begin?</h2><p>Choose a first Quest, review optional Health privacy later, manage Companion permissions when you need help, or enter Epic Ordinary only if story sounds useful.</p><form method="post" action="/onboarding/complete">'.$this->csrfField().'<div class="choice-list"><button name="next_step" value="quest" type="submit"><strong>Choose one meaningful action</strong><span>Create a small Quest without building an entire system.</span></button><button name="next_step" value="world" type="submit"><strong>Meet Epic Ordinary</strong><span>See how a fictional World can acknowledge real life without owning it.</span></button><button name="next_step" value="hearth" type="submit"><strong>Simply enter Hearth</strong><span>Look around first. Nothing else is required.</span></button></div></form></section><section class="surface unified-empty-state-guide-cards"><h2>Guide cards for a quiet start</h2><p>Empty areas will offer one safe next action and explain which source owns any record you create.</p><p><a href="/guide">Open the guide</a> · <a href="/privacy">Review privacy</a> · <a href="/companion/context">Companion permissions</a></p></section>';
        $this->render('Welcome',$body);
    }

    private function complete(): void
    {
        $account=Security::requireAccount();$this->csrf();
        try{$this->redirect($this->service->complete((string)$account['id'],(string)($_POST['next_step']??'')));}
        catch(RuntimeException $e){http_response_code(422);$this->render('Welcome','<section class="state-panel"><h1>Choose a way to begin.</h1><p>'.self::e($e->getMessage()).'</p><a href="/onboarding">Return to orientation</a></section>');}
    }

    private function render(string $title,string $body): void{echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><main id="main" class="page">'.$body.'</main></body></html>';}
    private function csrf():void{if(!Security::verifyCsrf(isset($_POST['csrf'])?(string)$_POST['csrf']:null))throw new RuntimeException('Your session changed. Please try again.');}
    private function csrfField():string{return '<input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'">';}
    private function redirect(string $path):never{header('Location: '.$path,true,303);exit;}
    private static function e(string $value):string{return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
