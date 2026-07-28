<?php

declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';

use Koravik\Platform\AccountData\AccountDataController;
use Koravik\Platform\Companion\CompanionContextController;
use Koravik\Platform\Companion\CompanionController;
use Koravik\Platform\Companion\CompanionLifecycleController;
use Koravik\Platform\Experience\ChronicleManagementController;
use Koravik\Platform\Hearth\HearthLayoutController;
use Koravik\Platform\Hearth\HearthLayoutService;
use Koravik\Platform\Notifications\NotificationController;
use Koravik\Platform\Notifications\NotificationService;
use Koravik\Platform\Privacy\PrivacyController;
use Koravik\Platform\Search\SearchController;
use Koravik\Platform\Security\AuthRecoveryController;
use Koravik\Platform\Security\Security;
use Koravik\Platform\Settings\SettingsController;

$method=strtoupper($_SERVER['REQUEST_METHOD']??'GET');$path=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/';
if($method==='GET'&&$path==='/health'){header('Content-Type: application/json; charset=utf-8');echo json_encode(['status'=>'ok','build'=>'020'],JSON_THROW_ON_ERROR);return;}
Security::startSession();ob_start();
$handled=(new AuthRecoveryController(database()))->handle($method,$path);
if(!$handled)$handled=(new AccountDataController(database()))->handle($method,$path);
if(!$handled)$handled=(new ChronicleManagementController(database()))->handle($method,$path);
if(!$handled)$handled=(new CompanionContextController(database()))->handle($method,$path);
if(!$handled)$handled=(new CompanionLifecycleController(database()))->handle($method,$path);
if(!$handled)$handled=(new CompanionController(database()))->handle($method,$path);
if(!$handled)$handled=(new HearthLayoutController(database()))->handle($method,$path);
if(!$handled)$handled=(new SettingsController(database()))->handle($method,$path);
if(!$handled)$handled=(new PrivacyController(database()))->handle($method,$path);
if(!$handled)$handled=(new SearchController(database()))->handle($method,$path);
if(!$handled)$handled=(new NotificationController(database()))->handle($method,$path);
if(!$handled)$handled=(new Koravik\Platform\ReturnExperience\ReturnController(database()))->handle();
if(!$handled)$handled=(new Koravik\Worlds\WorldController())->handle($method,$path);
if(!$handled)(new Koravik\Application())->run();
$html=(string)ob_get_clean();$account=Security::account();
if(!$account&&$method==='GET'&&$path==='/login'&&!str_contains($html,'href="/recover"'))$html=str_replace('</form>','</form><p><a href="/recover">Forgot your password?</a></p>',$html);
if($account&&$method==='GET'&&$path==='/hearth')$html=(new HearthLayoutService(database()))->apply($html,(string)$account['id']);
if($account&&$method==='GET'&&$path==='/chronicle')$html=str_replace('<section class="page-heading">','<section class="page-heading"><p><a class="button" href="/chronicle/new">New entry</a> <a href="/chronicle/manage">Manage Chronicle</a></p>',$html);
if($account&&$method==='GET'&&$path==='/settings'){$html=str_replace('Account export and deletion execution are not yet available; Koravik does not pretend otherwise.','Account export and staged account closure are available from <a href="/settings/data">Data controls</a>.',$html);if(!str_contains($html,'/settings/security'))$html=str_replace('</main>','<section class="settings-card"><h2>Security</h2><p>Change your password and invalidate older sessions.</p><a href="/settings/security">Open security settings</a></section></main>',$html);}
if($account&&str_contains($html,'<nav aria-label="Primary">')){
 if(!str_contains($html,'href="/worlds"'))$html=str_replace('</nav>','<a href="/worlds">Worlds</a></nav>',$html);
 if(!str_contains($html,'href="/companion"'))$html=str_replace('</nav>','<a href="/companion">Companion</a></nav>',$html);
 if(!str_contains($html,'href="/search"'))$html=str_replace('</nav>','<a href="/search">Search</a></nav>',$html);
 if(!str_contains($html,'href="/notifications"')){$count=(new NotificationService(database()))->unreadCount((string)$account['id']);$badge=$count>0?'<span class="notification-badge" aria-label="'.$count.' unread notifications">'.($count>9?'9+':$count).'</span>':'';$html=str_replace('</nav>','<a href="/notifications">Notifications'.$badge.'</a></nav>',$html);}
 if(!str_contains($html,'href="/privacy"'))$html=str_replace('</nav>','<a href="/privacy">Privacy</a></nav>',$html);
 if(!str_contains($html,'href="/settings"'))$html=str_replace('</nav>','<a href="/settings">Settings</a></nav>',$html);
}
echo $html;
