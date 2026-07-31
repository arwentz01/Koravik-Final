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
use Koravik\Platform\Hearth\DailyFocusController;
use Koravik\Platform\Hearth\DailyFocusService;
use Koravik\Platform\Hearth\DailyFocusView;
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
use Koravik\Worlds\WorldHomeController;
use Koravik\Worlds\WorldLifecycleController;

$method=strtoupper($_SERVER['REQUEST_METHOD']??'GET');$path=app_request_path();
if($method==='GET'&&$path==='/health'){header('Content-Type: application/json; charset=utf-8');echo json_encode(['status'=>'ok','build'=>'117','slice'=>'worlds-epic-ordinary-polish'],JSON_THROW_ON_ERROR);return;}
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
if(!$handled)$handled=(new DailyFocusController(database()))->handle($method,$path);
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
if(!$handled)$handled=(new WorldHomeController(database()))->handle($method,$path);
if(!$handled)$handled=(new WorldLifecycleController())->handle($method,$path);
if(!$handled)$handled=(new WorldProgressController())->handle($method,$path);
if(!$handled)$handled=(new ChapterTwoController())->handle($method,$path);
if(!$handled)$handled=(new Koravik\Worlds\WorldController())->handle($method,$path);
if(!$handled)(new Koravik\Application())->run();
$html=(string)ob_get_clean();$account=Security::account();
$replaceFirst=static function(string $search,string $replacement,string $subject): string {$position=strpos($subject,$search);return $position===false?$subject:substr_replace($subject,$replacement,$position,strlen($search));};
if(!$account&&$method==='GET'&&$path==='/login'){if(!str_contains($html,'href="/recover"'))$html=str_replace('</form>','</form><p><a href="/recover">Forgot your password?</a></p>',$html);if(!str_contains($html,'href="/register"'))$html=$replaceFirst('</section>','<p><a class="button secondary" href="/register">Create an account</a></p></section>',$html);}
if($account&&$method==='GET'&&$path==='/hearth'){
    $html=(new HearthLayoutService(database()))->apply($html,(string)$account['id']);
    $focus=(new DailyFocusView())->homePanel((new DailyFocusService(database()))->dashboard((string)$account['id']));
    $html=preg_replace('#<section><div class="section-heading"><h2>What matters now</h2>.*?</section>#s',$focus,$html,1)??$html;
    if(!str_contains($html,'hearth-focus.css'))$html=str_replace('</head>','<link rel="stylesheet" href="/assets/hearth-focus.css"></head>',$html);
    if(!str_contains($html,'hearth-polish.css'))$html=str_replace('</head>','<link rel="stylesheet" href="/assets/hearth-polish.css"></head>',$html);
    if(!str_contains($html,'hearth-orientation-grid')){
        $orientation='<section class="hearth-orientation-grid" aria-labelledby="hearth-orientation-title"><div><p class="eyebrow">Hearth dashboard</p><h2 id="hearth-orientation-title">Choose the right doorway for now.</h2><p>Hearth composes your day without taking ownership from Quests, Chronicle, Worlds, Companion, or Healing Home.</p></div><article><h3>Act</h3><p>Choose or continue one real-life commitment.</p><a href="/quests">Open Quests</a></article><article><h3>Reflect</h3><p>Preserve what matters when you decide it should be saved.</p><a href="/chronicle">Open Chronicle</a></article><article><h3>Enter the house</h3><p>Use Healing Home when you need place, meaning, and source-aware continuity.</p><a href="/home">Open Healing Home</a></article><article><h3>Continue story</h3><p>Worlds can interpret approved minimized facts into fiction.</p><a href="/worlds">Open Worlds</a></article></section>';
        $html=str_replace('</main>',$orientation.'</main>',$html);
    }
    if(!str_contains($html,'hearth-trust-strip')){
        $trust='<section class="hearth-trust-strip"><h2>What Hearth does</h2><p>Hearth is an orientation surface. It may link to source records, but it does not create Quests, Chronicle entries, Companion memory, World facts, or Healing Home state by itself.</p><p class="local-actions"><a class="button secondary" href="/home">Healing Home</a><a class="button secondary" href="/recovery-center">Recovery center</a><a class="button secondary" href="/settings/accessibility">Accessibility</a><a class="button secondary" href="/privacy">Privacy</a></p></section>';
        $html=str_replace('</main>',$trust.'</main>',$html);
    }
    if(str_contains($html,'<p>One meaningful next step is enough.</p>')&&!str_contains($html,'class="hearth-hero-actions"')){
        $html=str_replace('<p>One meaningful next step is enough.</p>','<p>One meaningful next step is enough.</p><p class="hearth-hero-actions"><a class="button secondary" href="/home">Open Healing Home</a><a class="button secondary" href="/hearth/focus">Set today’s focus</a><a class="button secondary" href="/guide">Open guide</a></p>',$html);
    }
    if(!str_contains($html,'/organizations'))$html=str_replace('</main>','<section class="surface"><h2>Organizations</h2><p>Open optional shared spaces for groups and communities without changing your personal Hearth.</p><a href="/organizations">View organizations</a></section></main>',$html);
    if(!str_contains($html,'/households'))$html=str_replace('</main>','<section class="surface"><h2>Households</h2><p>Coordinate private home life while keeping personal Quests and history independent.</p><a href="/households">View Households</a></section></main>',$html);
}
if($account&&$method==='GET'&&str_starts_with($path,'/quests')){
    if(!str_contains($html,'action-memory-polish.css'))$html=str_replace('</head>','<link rel="stylesheet" href="/assets/action-memory-polish.css"></head>',$html);
    if($path==='/quests'&&!str_contains($html,'quest-polish-panel')){
        $quests='<section class="action-memory-loop quest-polish-panel" aria-labelledby="quest-polish-title"><div><p class="eyebrow">Act</p><h2 id="quest-polish-title">Choose the next honest action.</h2><p>Quests are for real-life commitments, not performance. Keep the structure light, then preserve meaning in Chronicle only when you decide it is worth keeping.</p></div><div class="action-memory-cards"><article><h3>Begin small</h3><p>A single action is allowed to stay single. Projects and journeys can grow only when they need more shape.</p><a href="/quests/create">Create a Quest</a></article><article><h3>Reflect after action</h3><p>When a Quest changes you, move the meaning into Chronicle deliberately.</p><a href="/chronicle/new?context=quest_reflection&title=Quest%20reflection&tags=quest,reflection">Start a reflection</a></article><article><h3>See it in the house</h3><p>The Healing Home Quest Board can hold the current thread without turning it into a backlog.</p><a href="/home/rooms/quest_board">Open Quest Board</a></article></div></section>';
        $html=str_replace('</main>',$quests.'</main>',$html);
    }
    if(preg_match('#^/quests/([a-f0-9-]{36})$#',$path,$m)&&!str_contains($html,'reflection-bridge-panel')){
        $questId=htmlspecialchars($m[1],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        $bridge='<section class="reflection-bridge-panel" aria-labelledby="quest-reflection-title"><div><p class="eyebrow">After action</p><h2 id="quest-reflection-title">Preserve meaning intentionally.</h2><p>Completing or shaping this Quest does not force a Chronicle entry. If something matters, you can carry it across by choice.</p></div><p class="local-actions"><a class="button secondary" href="/chronicle/new?context=quest_reflection&title=Quest%20reflection&tags=quest,reflection">Reflect in Chronicle</a><a href="/home/rooms/quest_board">Open Quest Board</a><a href="/quests/'.$questId.'">Stay with this Quest</a></p></section>';
        $html=str_replace('</main>',$bridge.'</main>',$html);
    }
    if(!str_contains($html,'quest-ownership-strip')){
        $ownership='<section class="ownership-bridge-strip quest-ownership-strip"><h2>What Quests owns</h2><p>Quests own commitments, steps, schedules, progress, completion, and lifecycle. Chronicle owns saved reflections. Hearth and Healing Home compose links without rewriting either source.</p></section>';
        $html=str_replace('</main>',$ownership.'</main>',$html);
    }
}
if($account&&$method==='GET'&&str_starts_with($path,'/chronicle')){
    if(!str_contains($html,'action-memory-polish.css'))$html=str_replace('</head>','<link rel="stylesheet" href="/assets/action-memory-polish.css"></head>',$html);
    if($path==='/chronicle'){
        if(!str_contains($html,'href="/chronicle/manage"'))$html=str_replace('<section class="page-heading">','<section class="page-heading"><p class="local-actions"><a class="button" href="/chronicle/new">New entry</a> <a href="/chronicle/manage">Manage Chronicle</a></p>',$html);
        if(!str_contains($html,'chronicle-polish-panel')){
            $chronicle='<section class="action-memory-loop chronicle-polish-panel" aria-labelledby="chronicle-polish-title"><div><p class="eyebrow">Reflect</p><h2 id="chronicle-polish-title">Preserve what matters, not everything.</h2><p>Chronicle is the quiet shelf for authored memory. It keeps reflections private by default and separate from Quest mechanics, World interpretation, and Companion proposals.</p></div><div class="action-memory-cards"><article><h3>Write by choice</h3><p>A reflection exists here only after you save it.</p><a href="/chronicle/new">New Chronicle entry</a></article><article><h3>Return to action</h3><p>If the memory points toward a next step, let Quests own that work.</p><a href="/quests/create">Create a Quest</a></article><article><h3>Open the Journal Table</h3><p>Healing Home can show memory as place while Chronicle remains the source of the saved entry.</p><a href="/home/rooms/journal_table">Open Journal Table</a></article></div></section>';
            $html=str_replace('</main>',$chronicle.'</main>',$html);
        }
    }
    if($path==='/chronicle/new'&&!str_contains($html,'chronicle-editor-trust')){
        $editor='<section class="chronicle-editor-trust" aria-labelledby="chronicle-editor-trust-title"><p class="eyebrow">Before saving</p><h2 id="chronicle-editor-trust-title">This creates Chronicle memory only when you choose Save.</h2><p>Drafting here does not complete a Quest, notify anyone, create Companion memory, or change World State.</p><p class="local-actions"><a href="/quests">Open Quests</a><a href="/home/rooms/journal_table">Open Journal Table</a></p></section>';
        $html=str_replace('<form class="panel" method="post" action="/chronicle/entries">',$editor.'<form class="panel" method="post" action="/chronicle/entries">',$html);
    }
    if(!str_contains($html,'chronicle-ownership-strip')){
        $ownership='<section class="ownership-bridge-strip chronicle-ownership-strip"><h2>What Chronicle owns</h2><p>Chronicle owns saved prose, tags, provenance, archive, restore, and deletion behavior. Quests own actions. Worlds own interpretations. Companion proposals require approval before anything is saved here.</p></section>';
        $html=str_replace('</main>',$ownership.'</main>',$html);
    }
}
if($account&&$method==='GET'&&$path==='/settings'){$html=str_replace('Account export and deletion execution are not yet available; Koravik does not pretend otherwise.','Account export and staged account closure are available from <a href="/settings/data">Data controls</a>.',$html);if(!str_contains($html,'/settings/security'))$html=str_replace('</main>','<section class="settings-card trust-panel"><h2>Security</h2><p>Change your password and invalidate older sessions.</p><a href="/settings/security">Open security settings</a></section></main>',$html);if(in_array((string)($account['role']??''),['owner','admin'],true)&&!str_contains($html,'/system/mail'))$html=str_replace('</main>','<section class="settings-card"><h2>System operations</h2><p>Review Platform Mail and Beacon domains.</p><p><a href="/system/mail">Platform Mail</a> · <a href="/beacon/manage">Beacon management</a></p></section></main>',$html);}
if($account&&$method==='GET'&&$path==='/worlds/epic-ordinary'&&str_contains($html,'Status: Active')&&!str_contains($html,'/worlds/epic-ordinary/play'))$html=$replaceFirst('</section>','<p class="local-actions"><a class="button" href="/worlds/epic-ordinary/play">Continue story</a><a href="/worlds/epic-ordinary/progress">View progress</a><a href="/worlds/epic-ordinary/manage">Manage World</a></p></section>',$html);
if($method==='GET'&&preg_match('#^/gather/events/([a-f0-9-]{36})$#',$path,$m)&&!str_contains($html,'/agenda')){$id=htmlspecialchars($m[1],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');$actions='<section class="surface"><h2>Event tools</h2><p class="local-actions"><a class="button" href="/gather/events/'.$id.'/agenda">Agenda</a>'.($account?'<a class="button secondary" href="/gather/events/'.$id.'/day-of">Day-of</a><a class="button secondary" href="/gather/events/'.$id.'/scan">Scan QR</a><a class="button secondary" href="/gather/events/'.$id.'/reflect">Reflect</a>':'').'</p></section>';$html=str_replace('</main>',$actions.'</main>',$html);}
$html=(new AppShell())->apply($html,$account,$path);
$html=(new VisualSystem())->apply($html,$account,$path);
echo app_rewrite_html_paths($html);
