<?php

declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';

use Koravik\Districts\Beacon\BeaconController;
use Koravik\Districts\Beacon\BeaconManagementController;
use Koravik\Districts\Gather\GatherCompletionController;
use Koravik\Districts\Gather\GatherController;
use Koravik\Districts\Gather\GatherLifecycleController;
use Koravik\Districts\Quests\LivingQuestController;
use Koravik\Platform\AccountData\AccountDataController;
use Koravik\Platform\Companion\CompanionContextController;
use Koravik\Platform\Companion\CompanionController;
use Koravik\Platform\Companion\CompanionLifecycleController;
use Koravik\Platform\Experience\ChronicleManagementController;
use Koravik\Platform\Hearth\HearthLayoutController;
use Koravik\Platform\Hearth\HearthLayoutService;
use Koravik\Platform\Households\HouseholdController;
use Koravik\Platform\Journey\HealingHomeController;
use Koravik\Platform\Journey\JourneyArcController;
use Koravik\Platform\Mail\MailOperationsController;
use Koravik\Platform\Notifications\NotificationController;
use Koravik\Platform\Organizations\OrganizationController;
use Koravik\Platform\Orientation\OrientationController;
use Koravik\Platform\Privacy\PrivacyController;
use Koravik\Platform\Resilience\ResilienceController;
use Koravik\Platform\Search\SearchController;
use Koravik\Platform\Security\AuthRecoveryController;
use Koravik\Platform\Security\Security;
use Koravik\Platform\Settings\AccessibilityController;
use Koravik\Platform\Settings\SettingsController;
use Koravik\Platform\UI\AppShell;
use Koravik\Platform\UI\GuideController;
use Koravik\Platform\UI\VisualSystem;
use Koravik\Worlds\EpicOrdinary\ChapterTwoController;
use Koravik\Worlds\EpicOrdinary\WorldProgressController;
use Koravik\Worlds\WorldLifecycleController;

$method=strtoupper($_SERVER['REQUEST_METHOD']??'GET');$path=app_request_path();
if($method==='GET'&&$path==='/health'){header('Content-Type: application/json; charset=utf-8');echo json_encode(['status'=>'ok','build'=>'117'],JSON_THROW_ON_ERROR);return;}
Security::startSession();ob_start();
$handled=(new MailOperationsController(database()))->handle($method,$path);
if(!$handled)$handled=(new BeaconManagementController(database()))->handle($method,$path);
if(!$handled)$handled=(new BeaconController(database()))->handle($method,$path);
if(!$handled)$handled=(new OrganizationController(database()))->handle($method,$path);
if(!$handled)$handled=(new HouseholdController(database()))->handle($method,$path);
if(!$handled)$handled=(new GatherCompletionController(database()))->handle($method,$path);
if(!$handled)$handled=(new GatherLifecycleController(database()))->handle($method,$path);
if(!$handled)$handled=(new GatherController(database()))->handle($method,$path);
if(!$handled)$handled=(new OrientationController(database()))->handle($method,$path);
if(!$handled)$handled=(new GuideController())->handle($method,$path);
if(!$handled)$handled=(new AuthRecoveryController(database()))->handle($method,$path);
if(!$handled)$handled=(new AccountDataController(database()))->handle($method,$path);
if(!$handled)$handled=(new ChronicleManagementController(database()))->handle($method,$path);
if(!$handled)$handled=(new CompanionContextController(database()))->handle($method,$path);
if(!$handled)$handled=(new CompanionLifecycleController(database()))->handle($method,$path);
if(!$handled)$handled=(new CompanionController(database()))->handle($method,$path);
if(!$handled)$handled=(new HearthLayoutController(database()))->handle($method,$path);
if(!$handled)$handled=(new HealingHomeController(database()))->handle($method,$path);
if(!$handled)$handled=(new JourneyArcController(database()))->handle($method,$path);
if(!$handled)$handled=(new LivingQuestController(database()))->handle($method,$path);
if(!$handled)$handled=(new AccessibilityController(database()))->handle($method,$path);
if(!$handled)$handled=(new ResilienceController(database()))->handle($method,$path);
if(!$handled)$handled=(new SettingsController(database()))->handle($method,$path);
if(!$handled)$handled=(new PrivacyController(database()))->handle($method,$path);
if(!$handled)$handled=(new SearchController(database()))->handle($method,$path);
if(!$handled)$handled=(new NotificationController(database()))->handle($method,$path);
if(!$handled)$handled=(new Koravik\Platform\ReturnExperience\ReturnController(database()))->handle();
if(!$handled)$handled=(new WorldLifecycleController())->handle($method,$path);
if(!$handled)$handled=(new WorldProgressController())->handle($method,$path);
if(!$handled)$handled=(new ChapterTwoController())->handle($method,$path);
if(!$handled)$handled=(new Koravik\Worlds\WorldController())->handle($method,$path);
if(!$handled)(new Koravik\Application())->run();
$html=(string)ob_get_clean();$account=Security::account();
$replaceFirst=static function(string $search,string $replacement,string $subject): string {$position=strpos($subject,$search);return $position===false?$subject:substr_replace($subject,$replacement,$position,strlen($search));};
if(!$account&&$method==='GET'&&$path==='/login'){if(!str_contains($html,'href="/recover"'))$html=str_replace('</form>','</form><p><a href="/recover">Forgot your password?</a></p>',$html);if(!str_contains($html,'href="/register"'))$html=$replaceFirst('</section>','<p><a class="button secondary" href="/register">Create an account</a></p></section>',$html);}
if($account&&$method==='GET'&&$path==='/hearth'){$html=(new HearthLayoutService(database()))->apply($html,(string)$account['id']);if(!str_contains($html,'/organizations'))$html=str_replace('</main>','<section class="surface"><h2>Organizations</h2><p>Open optional shared spaces for groups and communities without changing your personal Hearth.</p><a href="/organizations">View organizations</a></section></main>',$html);if(!str_contains($html,'/households'))$html=str_replace('</main>','<section class="surface"><h2>Households</h2><p>Coordinate private home life while keeping personal Quests and history independent.</p><a href="/households">View Households</a></section></main>',$html);}
if($account&&$method==='GET'&&$path==='/chronicle')$html=str_replace('<section class="page-heading">','<section class="page-heading"><p class="local-actions"><a class="button" href="/chronicle/new">New entry</a> <a href="/chronicle/manage">Manage Chronicle</a></p>',$html);
if($account&&$method==='GET'&&$path==='/settings'){$html=str_replace('Account export and deletion execution are not yet available; Koravik does not pretend otherwise.','Account export and staged account closure are available from <a href="/settings/data">Data controls</a>.',$html);if(!str_contains($html,'/settings/security'))$html=str_replace('</main>','<section class="settings-card trust-panel"><h2>Security</h2><p>Change your password and invalidate older sessions.</p><a href="/settings/security">Open security settings</a></section></main>',$html);if(in_array((string)($account['role']??''),['owner','admin'],true)&&!str_contains($html,'/system/mail'))$html=str_replace('</main>','<section class="settings-card"><h2>System operations</h2><p>Review Platform Mail and Beacon domains.</p><p><a href="/system/mail">Platform Mail</a> · <a href="/beacon/manage">Beacon management</a></p></section></main>',$html);}
if($account&&$method==='GET'&&$path==='/worlds')$html=$replaceFirst('</section>','<p class="local-actions"><a href="/worlds/installed">Manage installed Worlds</a></p></section>',$html);
if($account&&$method==='GET'&&$path==='/worlds/epic-ordinary'&&str_contains($html,'Status: Active')&&!str_contains($html,'/worlds/epic-ordinary/play'))$html=$replaceFirst('</section>','<p class="local-actions"><a class="button" href="/worlds/epic-ordinary/play">Continue story</a><a href="/worlds/epic-ordinary/progress">View progress</a><a href="/worlds/epic-ordinary/manage">Manage World</a></p></section>',$html);
if($method==='GET'&&preg_match('#^/gather/events/([a-f0-9-]{36})$#',$path,$m)&&!str_contains($html,'/agenda')){$id=htmlspecialchars($m[1],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');$actions='<section class="surface"><h2>Event tools</h2><p class="local-actions"><a class="button" href="/gather/events/'.$id.'/agenda">Agenda</a>'.($account?'<a class="button secondary" href="/gather/events/'.$id.'/day-of">Day-of</a><a class="button secondary" href="/gather/events/'.$id.'/scan">Scan QR</a><a class="button secondary" href="/gather/events/'.$id.'/reflect">Reflect</a>':'').'</p></section>';$html=str_replace('</main>',$actions.'</main>',$html);}
$html=(new AppShell())->apply($html,$account,$path);
$html=(new VisualSystem())->apply($html,$account,$path);
echo app_rewrite_html_paths($html);
