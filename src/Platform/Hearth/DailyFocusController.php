<?php

declare(strict_types=1);

namespace Koravik\Platform\Hearth;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;
use Throwable;

final class DailyFocusController
{
    public function __construct(private readonly Database $database) {}

    public function handle(string $method,string $path):bool
    {
        if(!in_array($path,['/hearth/focus','/hearth/focus/clear'],true))return false;
        $account=Security::requireAccount();$accountId=(string)$account['id'];$service=new DailyFocusService($this->database);
        if($method==='GET'&&$path==='/hearth/focus'){$this->page('Today at Hearth',(new DailyFocusView())->editor($service->dashboard($accountId)));return true;}
        if($method==='POST'){if(!Security::verifyCsrf((string)($_POST['csrf']??''))){$this->page('Session changed','<section class="state-panel state-error" role="alert"><p class="eyebrow">Nothing was saved</p><h1>Your session changed.</h1><p>Return to Hearth and reopen today’s focus before trying again.</p><a class="button" href="/hearth">Return to Hearth</a></section>',419);return true;}
            if($path==='/hearth/focus/clear'){$service->clear($accountId);$_SESSION['flash']='Today’s focus cleared.';$this->go('/hearth');}
            $values=['intention'=>(string)($_POST['intention']??''),'priorities'=>(array)($_POST['priorities']??[])];
            try{$service->save($accountId,$values['intention'],$values['priorities']);$_SESSION['flash']='Today’s focus saved.';$this->go('/hearth');}
            catch(RuntimeException $e){$field=str_contains($e->getMessage(),'Quest')||str_contains($e->getMessage(),'priorit')?'priority_1':'intention';$this->page('Choose today’s focus',(new DailyFocusView())->editor($service->dashboard($accountId),$values,[$field=>$e->getMessage()]),422);return true;}
            catch(Throwable){$this->page('Choose today’s focus','<section class="notice error" role="alert"><h2>Today’s focus could not be saved.</h2><p>Your choices are still shown below. Please try again.</p></section>'.(new DailyFocusView())->editor($service->dashboard($accountId),$values),503);return true;}
        }
        return false;
    }

    private function go(string $path):never{header('Location: '.\app_with_base_path($path),true,303);exit;}
    private function page(string $title,string $body,int $status=200):void{http_response_code($status);echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"><link rel="stylesheet" href="/assets/hearth-focus.css"></head><body><a class="skip-link" href="#main">Skip to content</a><main id="main" class="page" tabindex="-1">'.$body.'</main></body></html>';}
    private static function e(string $v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
