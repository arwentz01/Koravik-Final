<?php

declare(strict_types=1);

namespace Koravik\Tests;

use Koravik\Platform\Households\HouseholdService;
use Koravik\Platform\Organizations\OrganizationService;
use Koravik\Platform\Security\Security;
use Koravik\Platform\Settings\AccessibilityService;
use Koravik\Platform\Resilience\FormErrors;
use Koravik\Platform\Resilience\ResilienceService;
use Koravik\Platform\Hearth\DailyFocus;
use Koravik\Platform\Hearth\DailyFocusService;
use Koravik\Platform\Hearth\DailyFocusView;
use Koravik\Platform\Journey\JourneyService;
use Koravik\Districts\Quests\QuestService;
use Koravik\Districts\Health\HealthService;
use Koravik\Platform\AccountData\AccountDataService;
use Koravik\Worlds\WorldHomeService;
use Koravik\Worlds\WorldHomeView;
use PDO;

final class ReleaseSuite
{
    public function __construct(private readonly TestRunner $runner, private readonly PDO $pdo) {}

    public function register(): void
    {
        $this->runner->test('migration inventory is fully applied', fn() => $this->migrations());
        $this->runner->test('critical schema matches the current product checkpoint', fn() => $this->schema());
        $this->runner->test('password and CSRF primitives fail closed', fn() => $this->security());
        $this->runner->test('Organization capabilities are contextual', fn() => $this->organizations());
        $this->runner->test('Household capabilities are contextual', fn() => $this->households());
        $this->runner->test('Gather management uses contextual authorization', fn() => $this->sourceContracts());
        $this->runner->test('login route is accessible and subdirectory-aware', fn() => $this->login());
        $this->runner->test('health identifies current slice', fn() => $this->health());
        $this->runner->test('mail and recovery operations are present', fn() => $this->operations());
        $this->runner->test('workers remain explicitly bounded', fn() => $this->workers());
        $this->runner->test('accessibility preferences persist, validate, and reset', fn() => $this->accessibility());
        $this->runner->test('workflow recovery is bounded and duplicate-safe', fn() => $this->resilience());
        $this->runner->test('Hearth daily focus composes only owned Quests', fn() => $this->dailyFocus());
        $this->runner->test('Worlds Home reviews only owned reactions', fn() => $this->worldsHome());
        $this->runner->test('Healing Home renders owned room continuity', fn() => $this->healingHome());
        $this->runner->test('Healing Home room detail preserves source ownership', fn() => $this->healingHomeRooms());
        $this->runner->test('Healing Home rest state is explicit and bounded', fn() => $this->healingHomeRestState());
        $this->runner->test('Healing Home room notes are private and bounded', fn() => $this->healingHomeRoomNotes());
        $this->runner->test('Healing Home Eastern Room opens from Epic Ordinary choice', fn() => $this->healingHomeEasternRoom());
        $this->runner->test('Healing Home relationship conversations are bounded and private', fn() => $this->healingHomeRelationshipConversations());
        $this->runner->test('Healing Home room map is visual, stateful, and accessible', fn() => $this->healingHomeRoomMap());
        $this->runner->test('Healing Home Fireplace explains and reviews World reactions', fn() => $this->healingHomeFireplaceReactionDetail());
        $this->runner->test('Healing Home Keepsake Shelf shows provenance and boundaries', fn() => $this->healingHomeKeepsakeShelf());
        $this->runner->test('Healing Home Journal Table starts Chronicle reflections safely', fn() => $this->healingHomeJournalTableReflectionBridge());
        $this->runner->test('Healing Home Garden opens from Caretaker conversation', fn() => $this->healingHomeGardenUnlock());
        $this->runner->test('Healing Home room expansion supports making, welcome, meaning, tending, and privacy', fn() => $this->healingHomeRoomExpansion());
        $this->runner->test('Quests and Chronicle expose the action-memory loop safely', fn() => $this->questChroniclePolish());
        $this->runner->test('Health check-ins remain private and publish only consented derived facts', fn() => $this->healthFoundation());
        $this->runner->test('Discovery, trust, campaign, follow-up, and Health trend slices are wired', fn() => $this->discoveryTrustCampaignFollowup());
        $this->runner->test('Layout, recurrence, media, and administration slices are wired', fn() => $this->layoutRecurrenceMediaAdmin());
        $this->runner->test('Builds 138 through 147 harden runtime coherence', fn() => $this->builds138147());
        $this->runner->test('Builds 148 through 157 deepen the core loop', fn() => $this->builds148157());
        $this->runner->test('Builds 158 through 167 polish public trust and admin readiness', fn() => $this->builds158167());
        $this->runner->test('Builds 168 through 177 improve onboarding, navigation, and everyday coherence', fn() => $this->builds168177());
        $this->runner->test('Builds 178 through 187 deepen Healing Home composition', fn() => $this->builds178187());
        $this->runner->test('Builds 188 through 197 make cross-module decisions actionable', fn() => $this->builds188197());
        $this->runner->test('Builds 198 through 207 mature the Source Inbox', fn() => $this->builds198207());
        $this->runner->test('Builds 208 through 217 make cross-module drafts durable', fn() => $this->builds208217());
        $this->runner->test('Epic Ordinary reclamation restores wonder without breaking boundaries', fn() => $this->epicOrdinaryReclamation());
        $this->runner->test('Moment Engine Foundation queues arrival scenes and Chronicle review safely', fn() => $this->momentEngineFoundation());
    }

    private function migrations(): void
    {
        $files = glob(KORAVIK_ROOT . '/database/migrations/*.sql') ?: [];
        $applied = $this->pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
        $expected = array_map(static fn(string $file): string => basename($file, '.sql'), $files);
        sort($expected); sort($applied);
        $this->runner->assert($expected === $applied, 'Configured schema is not at the repository migration checkpoint.');
    }

    private function schema(): void
    {
        foreach (['organization_teams','organization_quest_proposals','organization_recovery_records','households','household_memberships','household_quest_proposals','household_resources','household_recovery_records','platform_form_drafts','platform_idempotency_keys','auth_sessions','world_reaction_reviews','beacon_page_revisions','health_wellbeing_checkins','health_checkin_revisions','beacon_campaigns','gather_event_followups','platform_media_assets','beacon_page_blocks','chronicle_reflection_reviews','platform_media_links','quest_timeline_events','platform_moments'] as $table) {
            $statement = $this->pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=:table');
            $statement->execute(['table'=>$table]);
            $this->runner->assert((int)$statement->fetchColumn() === 1, "Missing table {$table}.");
        }
        foreach(['event_accent_color','event_header_style'] as $column){$statement=$this->pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name="gather_events" AND column_name=:column');$statement->execute(['column'=>$column]);$this->runner->assert((int)$statement->fetchColumn()===1,"Gather event branding missing {$column}.");}
        $type = (string)$this->pdo->query("SELECT COLUMN_TYPE FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='gather_events' AND column_name='owner_type'")->fetchColumn();
        $this->runner->assert(str_contains($type, "'organization'") && str_contains($type, "'household'"), 'Gather ownership contexts are incomplete.');
        $roomColumns=$this->pdo->query("SELECT column_name FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='healing_home_rooms'")->fetchAll(PDO::FETCH_COLUMN);
        foreach(['note_text','note_updated_at'] as $column)$this->runner->assert(in_array($column,$roomColumns,true), "Healing Home rooms missing {$column}.");
    }

    private function security(): void
    {
        $hash = password_hash('correct horse battery staple', PASSWORD_DEFAULT);
        $this->runner->assert(password_verify('correct horse battery staple', $hash), 'Password verification failed.');
        $this->runner->assert(!password_verify('wrong', $hash), 'Incorrect password was accepted.');
        $token = Security::csrfToken();
        $this->runner->assert(Security::verifyCsrf($token), 'Current CSRF token was rejected.');
        $this->runner->assert(!Security::verifyCsrf($token . 'x'), 'Invalid CSRF token was accepted.');
    }

    private function organizations(): void
    {
        $this->pdo->beginTransaction();
        try {
            $org='90000000-0000-4000-8000-000000000001';$owner='90000000-0000-4000-8000-000000000002';$member='90000000-0000-4000-8000-000000000003';
            $this->account($owner,'org-owner@test.invalid');$this->account($member,'org-member@test.invalid');
            $this->pdo->prepare('INSERT INTO organizations (id,name,primary_timezone,status,created_by_account_id,created_at,updated_at) VALUES (:id,"Test Org","UTC","active",:owner,UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$org,'owner'=>$owner]);
            $insert=$this->pdo->prepare('INSERT INTO organization_memberships (id,organization_id,account_id,role,status,joined_at,created_at,updated_at) VALUES (:id,:organization,:account,:role,"active",UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP())');
            $insert->execute(['id'=>'90000000-0000-4000-8000-000000000004','organization'=>$org,'account'=>$owner,'role'=>'owner']);
            $insert->execute(['id'=>'90000000-0000-4000-8000-000000000005','organization'=>$org,'account'=>$member,'role'=>'member']);
            $service=new OrganizationService(\database());
            $this->runner->assert($service->can($owner,$org,'manage_organization'), 'Owner lacks management capability.');
            $this->runner->assert(!$service->can($member,$org,'manage_members'), 'Member gained membership management.');
            $this->pdo->prepare('UPDATE organizations SET status="suspended" WHERE id=:id')->execute(['id'=>$org]);
            $this->runner->assert(!$service->can($owner,$org,'create_content'), 'Suspended Organization still permits content creation.');
        } finally {$this->pdo->rollBack();}
    }

    private function households(): void
    {
        $this->pdo->beginTransaction();
        try {
            $house='91000000-0000-4000-8000-000000000001';$owner='91000000-0000-4000-8000-000000000002';$member='91000000-0000-4000-8000-000000000003';
            $this->account($owner,'house-owner@test.invalid');$this->account($member,'house-member@test.invalid');
            $this->pdo->prepare('INSERT INTO households (id,name,primary_timezone,status,created_by_account_id,created_at,updated_at) VALUES (:id,"Test Home","UTC","active",:owner,UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$house,'owner'=>$owner]);
            $insert=$this->pdo->prepare('INSERT INTO household_memberships (id,household_id,account_id,role,status,joined_at,created_at,updated_at) VALUES (:id,:household,:account,:role,"active",UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP())');
            $insert->execute(['id'=>'91000000-0000-4000-8000-000000000004','household'=>$house,'account'=>$owner,'role'=>'owner']);
            $insert->execute(['id'=>'91000000-0000-4000-8000-000000000005','household'=>$house,'account'=>$member,'role'=>'member']);
            $service=new HouseholdService(\database());
            $this->runner->assert($service->can($member,$house,'coordinate'), 'Member cannot coordinate within Household.');
            $this->runner->assert(!$service->can($member,$house,'manage_members'), 'Member gained membership management.');
            $this->pdo->prepare('UPDATE households SET status="archived" WHERE id=:id')->execute(['id'=>$house]);
            $this->runner->assert(!$service->can($owner,$house,'manage_resources'), 'Archived Household still permits resource mutation.');
        } finally {$this->pdo->rollBack();}
    }

    private function sourceContracts(): void
    {
        foreach (['GatherCommandService.php','GatherCommunicationService.php','GatherDayOfService.php','GatherLifecycleService.php','GatherWorkflowService.php'] as $file) {
            $source=(string)file_get_contents(KORAVIK_ROOT.'/src/Districts/Gather/'.$file);
            $this->runner->assert(str_contains($source,'GatherAuthorization'), "{$file} bypasses GatherAuthorization.");
        }
    }

    private function login(): void
    {
        [$status,$body]=$this->http('/login');
        $this->runner->assert($status===200, "Login returned HTTP {$status}.");
        foreach (['class="skip-link"','<main','<label',\app_with_base_path('/assets/app.css'),'action="'.\app_with_base_path('/login').'"'] as $needle) $this->runner->assert(str_contains($body,$needle), "Login is missing {$needle}.");
    }

    private function health(): void
    {
        [$status,$body]=$this->http('/health');
        $payload=json_decode($body,true);
        $this->runner->assert($status===200 && ($payload['status']??'')==='ok' && ($payload['build']??'')==='217' && ($payload['slice']??'')==='durable-cross-module-drafts', 'Health checkpoint does not identify the current slice.');
    }

    private function operations(): void
    {
        $columns=$this->pdo->query("SELECT column_name FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='platform_mail_deliveries'")->fetchAll(PDO::FETCH_COLUMN);
        foreach (['status','attempts','failure_reason','claimed_at','recovered_at','cancelled_at'] as $column) $this->runner->assert(in_array($column,$columns,true), "Mail operations missing {$column}.");
        foreach (['organization_recovery_records','household_recovery_records'] as $table) $this->runner->assert((int)$this->pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn()>=0, "Cannot read {$table}.");
    }

    private function workers(): void
    {
        foreach (['mail-worker.php'=>'min(100','gather-reminder-worker.php'=>'min(500','worker.php'=>'min(100'] as $file=>$bound) {
            $source=(string)file_get_contents(KORAVIK_ROOT.'/tools/'.$file);
            $this->runner->assert(str_contains($source,$bound), "{$file} has no expected finite bound.");
        }
    }

    private function accessibility(): void
    {
        $account='92000000-0000-4000-8000-000000000001';
        try {
            $this->pdo->prepare('DELETE FROM audit_log WHERE subject_id=:account')->execute(['account'=>$account]);
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id=:account')->execute(['account'=>$account]);
            $this->account($account,'accessibility@test.invalid');
            $service=new AccessibilityService(\database());
            $service->save($account,[
                'text_scale'=>'larger','typeface'=>'readable','content_spacing'=>'relaxed',
                'reading_width'=>'narrow','emphasize_links'=>'1','enhanced_focus'=>'1',
                'high_contrast'=>'1','reduced_motion'=>'1',
            ]);
            $saved=$service->get($account);
            foreach(['text_scale'=>'larger','typeface'=>'readable','content_spacing'=>'relaxed','reading_width'=>'narrow'] as $key=>$value) {
                $this->runner->assert($saved[$key]===$value, "Accessibility preference {$key} was not saved.");
            }
            foreach(['emphasize_links','enhanced_focus','high_contrast','reduced_motion'] as $key) {
                $this->runner->assert((int)$saved[$key]===1, "Accessibility switch {$key} was not saved.");
            }
            $service->reset($account);
            $reset=$service->get($account);
            $this->runner->assert($reset['text_scale']==='standard' && (int)$reset['enhanced_focus']===0, 'Accessibility reset did not restore defaults.');
            try {$service->save($account,['text_scale'=>'extreme']);$valid=false;} catch (\RuntimeException) {$valid=true;}
            $this->runner->assert($valid, 'Invalid accessibility values were accepted.');
            $css=(string)file_get_contents(KORAVIK_ROOT.'/public/assets/accessibility.css');
            foreach(['.text-larger','.typeface-readable','.spacing-relaxed','.emphasize-links','.enhanced-focus','.width-narrow','.reduce-motion'] as $selector) {
                $this->runner->assert(str_contains($css,$selector), "Accessibility stylesheet is missing {$selector}.");
            }
        } finally {
            $this->pdo->prepare('DELETE FROM audit_log WHERE subject_id=:account')->execute(['account'=>$account]);
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id=:account')->execute(['account'=>$account]);
        }
    }

    private function resilience(): void
    {
        $account='93000000-0000-4000-8000-000000000001';
        try {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id=:account')->execute(['account'=>$account]);
            $this->account($account,'resilience@test.invalid');
            $service=new ResilienceService(\database());
            $service->saveDraft($account,'quest.create',['title'=>'Keep this','password'=>'never keep this','csrf'=>'never keep this']);
            $drafts=$service->drafts($account);
            $this->runner->assert(count($drafts)===1 && ($drafts[0]['payload']['title']??'')==='Keep this', 'Draft content was not recovered.');
            $this->runner->assert(!isset($drafts[0]['payload']['password'],$drafts[0]['payload']['csrf']), 'Sensitive form fields entered draft storage.');
            $requestKey=hash('sha256','resilience-test');
            $this->runner->assert($service->claim($account,'quest.create',$requestKey), 'First idempotency claim failed.');
            $this->runner->assert(!$service->claim($account,'quest.create',$requestKey), 'Duplicate idempotency claim was accepted.');
            $this->runner->assert($service->touchSession($account,'session-one','Test Browser','127.0.0.1'), 'Session registration failed.');
            $sessions=$service->sessions($account,'session-two');
            $this->runner->assert(count($sessions)===1 && !$sessions[0]['current'], 'Session inventory is incorrect.');
            $this->runner->assert($service->revokeSession($account,(string)$sessions[0]['id'],'session-two'), 'Other session could not be revoked.');
            $this->runner->assert(!$service->touchSession($account,'session-one','Test Browser','127.0.0.1'), 'Revoked session became active again.');
            $errors=FormErrors::required(['title'=>''],['title'=>'Title']);
            $summary=FormErrors::summary($errors);
            $this->runner->assert(str_contains($summary,'role="alert"')&&str_contains($summary,'href="#title"'), 'Accessible error summary contract failed.');
        } finally {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id=:account')->execute(['account'=>$account]);
        }
    }

    private function dailyFocus(): void
    {
        $account='94000000-0000-4000-8000-000000000001';$other='94000000-0000-4000-8000-000000000002';
        try {
            $this->pdo->prepare('DELETE FROM audit_log WHERE account_id IN (:account,:other)')->execute(['account'=>$account,'other'=>$other]);
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id IN (:account,:other)')->execute(['account'=>$account,'other'=>$other]);
            $this->account($account,'focus@test.invalid');$this->account($other,'focus-other@test.invalid');
            $quests=new QuestService(\database());
            $first=$quests->create($account,'Call the person I miss','',['starts_on'=>gmdate('Y-m-d')]);
            $second=$quests->create($account,'Make dinner slowly','',['starts_on'=>gmdate('Y-m-d')]);
            $foreign=$quests->create($other,'Private other Quest','',['starts_on'=>gmdate('Y-m-d')]);
            $lookup=$this->pdo->prepare('SELECT id FROM quest_occurrences WHERE quest_id=:quest');
            $lookup->execute(['quest'=>$first]);$firstOccurrence=(string)$lookup->fetchColumn();
            $lookup->execute(['quest'=>$second]);$secondOccurrence=(string)$lookup->fetchColumn();
            $lookup->execute(['quest'=>$foreign]);$foreignOccurrence=(string)$lookup->fetchColumn();
            $service=new DailyFocusService(\database());
            $service->save($account,'Show up with care',[$secondOccurrence,$firstOccurrence]);
            $dashboard=$service->dashboard($account);
            $this->runner->assert(($dashboard['focus']['intention']??'')==='Show up with care','Daily intention was not saved.');
            $this->runner->assert(array_column($dashboard['focus']['entries'],'quest_id')===[$second,$first],'Daily priorities did not preserve their order.');
            $html=(new DailyFocusView())->homePanel($dashboard);
            foreach(['aria-labelledby="daily-focus-title"','Quest','Adjust focus','/quests/'.$second] as $needle)$this->runner->assert(str_contains($html,$needle),"Daily Focus UI is missing {$needle}.");
            $index=(string)file_get_contents(KORAVIK_ROOT.'/public/index.php');
            foreach(['hearth-orientation-grid','Choose the right doorway for now.','Open Healing Home','Hearth is an orientation surface','hearth-polish.css'] as $needle)$this->runner->assert(str_contains($index,$needle),"Hearth dashboard polish composition is missing {$needle}.");
            $css=(string)file_get_contents(KORAVIK_ROOT.'/public/assets/hearth-polish.css');
            foreach(['.hearth-hero-actions','.hearth-orientation-grid','.hearth-trust-strip','forced-colors'] as $needle)$this->runner->assert(str_contains($css,$needle),"Hearth polish CSS is missing {$needle}.");
            $exportId=(new AccountDataService(\database()))->requestExport($account,'json');
            $export=(new AccountDataService(\database()))->export($account,$exportId);
            $exportData=json_decode((string)$export['export_json'],true);
            $this->runner->assert(count($exportData['hearth_daily_focus']??[])===1 && count($exportData['hearth_daily_focus_entries']??[])===2,'Account export omitted Daily Focus data.');
            try{$service->save($account,'Must not cross ownership',[$foreignOccurrence]);$denied=false;}catch(\RuntimeException){$denied=true;}
            $this->runner->assert($denied,'Daily Focus accepted another account’s Quest occurrence.');
            try{DailyFocus::normalize('',array_fill(0,4,$firstOccurrence));$bounded=false;}catch(\RuntimeException){$bounded=true;}
            $this->runner->assert($bounded,'Daily Focus accepted more than three priorities.');
            $service->clear($account);
            $this->runner->assert($service->dashboard($account)['focus']===null,'Daily Focus did not clear.');
        } finally {
            $this->pdo->prepare('DELETE FROM audit_log WHERE account_id IN (:account,:other)')->execute(['account'=>$account,'other'=>$other]);
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id IN (:account,:other)')->execute(['account'=>$account,'other'=>$other]);
        }
    }

    private function worldsHome(): void
    {
        $account='95000000-0000-4000-8000-000000000001';$other='95000000-0000-4000-8000-000000000002';
        $installation='95000000-0000-4000-8000-000000000003';$otherInstallation='95000000-0000-4000-8000-000000000004';
        $reaction='95000000-0000-4000-8000-000000000005';$otherReaction='95000000-0000-4000-8000-000000000006';
        try {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id IN (:account,:other)')->execute(['account'=>$account,'other'=>$other]);
            $this->account($account,'world-home@test.invalid');$this->account($other,'world-home-other@test.invalid');
            $installationInsert=$this->pdo->prepare('INSERT INTO world_installations (id,account_id,world_key,status,installed_at) VALUES (:id,:account,"epic-ordinary","active",UTC_TIMESTAMP())');
            $installationInsert->execute(['id'=>$installation,'account'=>$account]);
            $installationInsert->execute(['id'=>$otherInstallation,'account'=>$other]);
            $progressInsert=$this->pdo->prepare('INSERT INTO world_narrative_progress (installation_id,current_arc,current_chapter,current_scene,updated_at) VALUES (:id,"making-refuge","the-eastern-room","doorway",UTC_TIMESTAMP())');
            $progressInsert->execute(['id'=>$installation]);$progressInsert->execute(['id'=>$otherInstallation]);
            $this->pdo->prepare('INSERT INTO world_objectives (id,installation_id,objective_key,title,description,status,created_at) VALUES ("95000000-0000-4000-8000-000000000011",:installation,"choose-refuge","Decide what kind of refuge this will become","Choose what the restored space should offer.","active",UTC_TIMESTAMP())')->execute(['installation'=>$installation]);
            $this->pdo->prepare('INSERT INTO world_keepsakes (id,installation_id,keepsake_key,name,description,source_scene,acquired_at) VALUES ("95000000-0000-4000-8000-000000000012",:installation,"linen-thread","A Linen Thread","A pale thread from the restored room.","eastern-room-purpose",UTC_TIMESTAMP())')->execute(['installation'=>$installation]);
            $this->pdo->prepare('INSERT INTO world_fact_permissions (installation_id,fact_key,granted,explanation,granted_at,updated_at) VALUES (:installation,"quest.completed",1,"Quest completion may shape future World reactions.",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['installation'=>$installation]);
            $reactionInsert=$this->pdo->prepare('INSERT INTO world_reactions (id,installation_id,source_event_id,title,message,explanation,source_fact_key,source_fact_summary,rule_key,interpreted_at,created_at) VALUES (:id,:installation,:event,"The house noticed","A light returned.","An approved completion fact matched a World rule.","quest.completed","A Quest occurrence was completed.","caretaker-notices",UTC_TIMESTAMP(),UTC_TIMESTAMP())');
            $reactionInsert->execute(['id'=>$reaction,'installation'=>$installation,'event'=>'95000000-0000-4000-8000-000000000007']);
            $reactionInsert->execute(['id'=>$otherReaction,'installation'=>$otherInstallation,'event'=>'95000000-0000-4000-8000-000000000008']);
            $service=new WorldHomeService(\database());$dashboard=$service->dashboard($account);
            $this->runner->assert(($dashboard['active_world']['world_key']??'')==='epic-ordinary','Worlds Home did not compose the active World.');
            $this->runner->assert(count($dashboard['reactions'])===1&&$dashboard['reactions'][0]['id']===$reaction,'Worlds Home exposed another account’s reaction.');
            $html=(new WorldHomeView())->render($dashboard);
            foreach(['Continue story','Why did this change?','Mark reviewed','fictional World State','Epic Ordinary is waiting at the threshold.','Current objective','Latest keepsake','Received fact','What Worlds own','Open Eastern Room'] as $needle)$this->runner->assert(str_contains($html,$needle),"Worlds Home UI is missing {$needle}.");
            $homeCss=(string)file_get_contents(KORAVIK_ROOT.'/public/assets/world-home.css');
            foreach(['.world-story-gateway','.world-state-ledger','.world-continuation-cues','.world-reaction-mini','.world-trust-strip','forced-colors'] as $selector)$this->runner->assert(str_contains($homeCss,$selector),"World home polish CSS is missing {$selector}.");
            $chapterSource=(string)file_get_contents(KORAVIK_ROOT.'/src/Worlds/EpicOrdinary/ChapterTwoController.php');
            foreach(['eastern-room-preview','The door is unlocked by consent, not productivity.','refuge-choice-list','What this choice changes','Visit the Eastern Room'] as $needle)$this->runner->assert(str_contains($chapterSource,$needle),"Epic Ordinary continuation polish is missing {$needle}.");
            foreach(['Chapter Three · The Listening Wall','chapter-three/begin','listening-choice-list','The wall answers in your own words','Hear the Library echo'] as $needle)$this->runner->assert(str_contains($chapterSource,$needle),"Epic Ordinary Chapter Three UI is missing {$needle}.");
            $chapterService=(string)file_get_contents(KORAVIK_ROOT.'/src/Worlds/EpicOrdinary/ChapterTwoService.php');
            foreach(['beginChapterThree','chooseListeningTruth','listening-wall-truth','choose-what-the-house-keeps','chapter.three.choice'] as $needle)$this->runner->assert(str_contains($chapterService,$needle),"Epic Ordinary Chapter Three service is missing {$needle}.");
            $chapterCss=(string)file_get_contents(KORAVIK_ROOT.'/public/assets/chapter-three.css');
            foreach(['.chapter-three-threshold','.listening-choice-list','Fictional truth','forced-colors'] as $selector)$this->runner->assert(str_contains($chapterCss,$selector),"Epic Ordinary Chapter Three CSS is missing {$selector}.");
            $journeySource=(string)file_get_contents(KORAVIK_ROOT.'/src/Platform/Journey/JourneyService.php');
            foreach(['listening_wall_echo','An echo entered the Library','listening_wall_keepsake'] as $needle)$this->runner->assert(str_contains($journeySource,$needle),"Chapter Three Healing Home consequence is missing {$needle}.");
            $companionSource=(string)file_get_contents(KORAVIK_ROOT.'/src/Platform/Companion/CompanionController.php');
            foreach(['Companion help center','companion-state-strip','Need your decision','Approved, not executed','How control works','companion-center.css'] as $needle)$this->runner->assert(str_contains($companionSource,$needle),"Companion proposal center is missing {$needle}.");
            $companionCss=(string)file_get_contents(KORAVIK_ROOT.'/public/assets/companion-center.css');
            foreach(['.companion-state-strip','.companion-workspace','.proposal-center-grid','forced-colors'] as $selector)$this->runner->assert(str_contains($companionCss,$selector),"Companion proposal center CSS is missing {$selector}.");
            $returnController=(string)file_get_contents(KORAVIK_ROOT.'/src/Platform/ReturnExperience/ReturnController.php');
            foreach(['You do not have to catch up.','Choose one thread','Unfinished drafts','Unread notices','return-experience.css'] as $needle)$this->runner->assert(str_contains($returnController,$needle),"Welcome-Back Experience is missing {$needle}.");
            $returnService=(string)file_get_contents(KORAVIK_ROOT.'/src/Platform/ReturnExperience/ReturnService.php');
            foreach(['platform_form_drafts','target_url','current_chapter'] as $needle)$this->runner->assert(str_contains($returnService,$needle),"Welcome-Back composition is missing {$needle}.");
            $returnCss=(string)file_get_contents(KORAVIK_ROOT.'/public/assets/return-experience.css');
            foreach(['.welcome-back','.return-continuity-grid','.return-continuity-card','forced-colors'] as $selector)$this->runner->assert(str_contains($returnCss,$selector),"Welcome-Back CSS is missing {$selector}.");
            $questManagement=(string)file_get_contents(KORAVIK_ROOT.'/src/Districts/Quests/LivingQuestController.php');
            foreach(['/quests/manage','updateDetails','rescheduleNext','Quest history','quest-management.css'] as $needle)$this->runner->assert(str_contains($questManagement,$needle),"Complete Quest Management is missing {$needle}.");
            $questService=(string)file_get_contents(KORAVIK_ROOT.'/src/Districts/Quests/QuestService.php');
            foreach(['function management','function history','function updateDetails','function rescheduleNext'] as $needle)$this->runner->assert(str_contains($questService,$needle),"Quest management service is missing {$needle}.");
            $questCss=(string)file_get_contents(KORAVIK_ROOT.'/public/assets/quest-management.css');
            foreach(['.quest-management-grid','.quest-manage-card','.quest-history','forced-colors'] as $selector)$this->runner->assert(str_contains($questCss,$selector),"Quest management CSS is missing {$selector}.");
            $chronicleManagement=(string)file_get_contents(KORAVIK_ROOT.'/src/Platform/Experience/ChronicleManagementController.php');
            foreach(['chronicle-detail-layout','Private Chronicle record','Entry lifecycle','Delete permanently','chronicle-management.css'] as $needle)$this->runner->assert(str_contains($chronicleManagement,$needle),"Complete Chronicle Lifecycle is missing {$needle}.");
            $chronicleCss=(string)file_get_contents(KORAVIK_ROOT.'/public/assets/chronicle-management.css');
            foreach(['.chronicle-detail-layout','.chronicle-provenance','.chronicle-lifecycle','forced-colors'] as $selector)$this->runner->assert(str_contains($chronicleCss,$selector),"Chronicle management CSS is missing {$selector}.");
            $organizationDashboard=(string)file_get_contents(KORAVIK_ROOT.'/src/Platform/Organizations/OrganizationController.php');
            foreach(['organization-command-bar','Your operating role','Gather owns event truth','Beacon owns public links','organization-dashboard.css'] as $needle)$this->runner->assert(str_contains($organizationDashboard,$needle),"Organization Operating Dashboard is missing {$needle}.");
            $organizationCss=(string)file_get_contents(KORAVIK_ROOT.'/public/assets/organization-dashboard.css');
            foreach(['.organization-command-bar','.organization-role-panel','.organization-source-grid','forced-colors'] as $selector)$this->runner->assert(str_contains($organizationCss,$selector),"Organization dashboard CSS is missing {$selector}.");
            $householdDashboard=(string)file_get_contents(KORAVIK_ROOT.'/src/Platform/Households/HouseholdController.php');
            foreach(['household-summary','Your Household role','Gather owns event truth','Recent Household activity','household-dashboard.css'] as $needle)$this->runner->assert(str_contains($householdDashboard,$needle),"Household Home Dashboard is missing {$needle}.");
            $householdCss=(string)file_get_contents(KORAVIK_ROOT.'/public/assets/household-dashboard.css');
            foreach(['.household-summary','.household-role-note','.household-source-grid','forced-colors'] as $selector)$this->runner->assert(str_contains($householdCss,$selector),"Household dashboard CSS is missing {$selector}.");
            $gatherParticipant=(string)file_get_contents(KORAVIK_ROOT.'/src/Districts/Gather/GatherController.php');
            foreach(['Your Gather participation','participant-journey','Signup commitments','Release this commitment','Cancel RSVP','gather-participant.css','gather.css','gather-host-operating-system','Gather Event Lifecycle Map','agenda','Day-of operations','Closeout and aftercare','gather-signup-builder','gather-public-view-page','gather-organization-branding','gather-better-than-signupgenius','Food and potluck','Equipment and gear','Attendance is required: if someone is not going, they cannot claim this need.','max_quantity_per_commitment'] as $needle)$this->runner->assert(str_contains($gatherParticipant,$needle),"Gather Participant Journey is missing {$needle}.");
            $gatherCommand=(string)file_get_contents(KORAVIK_ROOT.'/src/Districts/Gather/GatherOperationsController.php');
            foreach(['gather-event-editor','gather-mod-signups','gather-mod-slot-card','Assign RSVP','Delete signup','/details','/assign','/delete','gather.css','gather-command-roadmap','Step 1 · Event basics','Step 3 · Food, shifts, equipment, supplies','Add this signup need','Use Step 3 above','event_accent_color','event_header_style','Save event details and branding'] as $needle)$this->runner->assert(str_contains($gatherCommand,$needle),"Gather moderator command center is missing {$needle}.");
            $gatherServiceSource=(string)file_get_contents(KORAVIK_ROOT.'/src/Districts/Gather/GatherService.php');
            foreach(['categoryKey','categoryLabel','organization_brand_color','RSVP yes before claiming this signup.','max_quantity_per_commitment',"'require_rsvp'=>1"] as $needle)$this->runner->assert(str_contains($gatherServiceSource,$needle),"Gather signup service is missing {$needle}.");
            $gatherCommandService=(string)file_get_contents(KORAVIK_ROOT.'/src/Districts/Gather/GatherCommandService.php');
            foreach(['function updateDetails','function updateSlot','function deleteSlot','function assignSlot','categoryKey','Quantity needed cannot be below active assigned quantity'] as $needle)$this->runner->assert(str_contains($gatherCommandService,$needle),"Gather moderator service is missing {$needle}.");
            $gatherWorkflow=(string)file_get_contents(KORAVIK_ROOT.'/src/Districts/Gather/GatherWorkflowService.php');
            foreach(['commitment_id','checkin','cancelCommitment','category_label','Equipment and gear','max_quantity_per_commitment'] as $needle)$this->runner->assert(str_contains($gatherWorkflow,$needle),"Gather participant service is missing {$needle}.");
            $gatherCss=(string)file_get_contents(KORAVIK_ROOT.'/public/assets/gather.css');
            foreach(['.gather-mod-signups','.gather-mod-slot-card','.gather-inline-assign','.gather-signup-category','.gather-public-view-page','.gather-event-editor .form-grid','.gather-command-roadmap','.gather-command-step','.gather-template-strip','.form-note','.gather-event-header','.header-style-forest','.header-style-custom','forced-colors'] as $selector)$this->runner->assert(str_contains($gatherCss,$selector),"Gather visual system CSS is missing {$selector}.");
            $appCss=(string)file_get_contents(KORAVIK_ROOT.'/public/assets/app.css');
            foreach(['Koravik Design System v1','--koravik-navy','--koravik-forest','--koravik-gold','--koravik-parchment'] as $selector)$this->runner->assert(str_contains($appCss,$selector),"Koravik brand system CSS is missing {$selector}.");
            $visualSystemCss=(string)file_get_contents(KORAVIK_ROOT.'/public/assets/visual-system.css');
            foreach(['Global visual reset','Site-wide UI/UX cleanup','input[type="checkbox"]','input[type="radio"]','minmax(min(100%,260px),1fr)','label:has(input[type="checkbox"])','.koravik-ui .page','.koravik-ui .page-header','.koravik-ui .page-header h1','.koravik-ui table','.koravik-ui .section-heading','.koravik-ui .surface>*:first-child','.koravik-ui .surface form'] as $selector)$this->runner->assert(str_contains($visualSystemCss,$selector),"Global visual system CSS is missing {$selector}.");
            $appShellCss=(string)file_get_contents(KORAVIK_ROOT.'/public/assets/app-shell.css');
            foreach(['.shell-navigation','grid-template-columns:minmax(0,1fr) auto auto','overflow-x:auto','@media(max-width:1040px)','.account-menu-panel','Koravik Design System v1 shell'] as $selector)$this->runner->assert(str_contains($appShellCss,$selector),"App shell visual system CSS is missing {$selector}.");
            $appShellSource=(string)file_get_contents(KORAVIK_ROOT.'/src/Platform/UI/AppShell.php');
            $visualSystemSource=(string)file_get_contents(KORAVIK_ROOT.'/src/Platform/UI/VisualSystem.php');
            $frontController=(string)file_get_contents(KORAVIK_ROOT.'/public/index.php');
            foreach(['brand-v1'] as $selector){$this->runner->assert(str_contains($appShellSource,$selector),"App shell source is missing {$selector}.");$this->runner->assert(str_contains($visualSystemSource,$selector),"Visual system source is missing {$selector}.");$this->runner->assert(str_contains($frontController,$selector),"Front controller asset rewrite is missing {$selector}.");}
            $gatherParticipantCss=(string)file_get_contents(KORAVIK_ROOT.'/public/assets/gather-participant.css');
            foreach(['.participant-journey','.participant-slot-grid','.participant-checkin','forced-colors'] as $selector)$this->runner->assert(str_contains($gatherParticipantCss,$selector),"Gather participant CSS is missing {$selector}.");
            $beaconBuilder=(string)file_get_contents(KORAVIK_ROOT.'/src/Districts/Beacon/BeaconController.php');
            foreach(['Beacon page builder','Mobile preview','Before publishing','Revision history','beacon-public-shell','beacon-builder.css','Beacon Mission Control','beacon-domain-routing-console','beacon-campaign-pipeline','beacon-public-trust-layer','beacon-public-presence-layer','Beacon Command Overview','beacon-command-overview','beacon-creation-studio','beacon-builder-guidance','beacon-block-template-strip','beacon-public-source-boundary','Step-by-step Beacon builder','Link, Text, E-mail, Call, SMS, V-card, WhatsApp, WI-FI, PDF, App, Images, Video, Social Media, Event, or 2D Barcode','beacon-type-button','beacon-compact-list','editPageV2','beacon-drawer-scrim','beacon-type-drawer','beaconDrawerPanels','beaconDrawerScript','Step 3 ·','indexV2','beacon-home','beacon-file-table','beacon-file-row','createActionBeacon','/beacon/actions','Pick a type. Fill the drawer. Publish when it looks right.'] as $needle)$this->runner->assert(str_contains($beaconBuilder,$needle),"Beacon Page Builder is missing {$needle}.");
            $beaconService=(string)file_get_contents(KORAVIK_ROOT.'/src/Districts/Beacon/BeaconService.php');
            foreach(['ownedPage','updatePage','beacon_page_revisions','BEACON_ACTION_TYPES','email','call','sms','vcard','whatsapp','wifi','pdf','app','image','video','social_media','barcode_2d'] as $needle)$this->runner->assert(str_contains($beaconService,$needle),"Beacon page service is missing {$needle}.");
            $beaconCss=(string)file_get_contents(KORAVIK_ROOT.'/public/assets/beacon-builder.css');
            foreach(['.beacon-builder','.beacon-phone-preview','.beacon-public-shell','.route-beacon .beacon-mission-control','.route-beacon article.surface','.beacon-command-overview','.beacon-pipeline-steps','.beacon-creation-studio','.beacon-block-template-strip','.beacon-public-source-boundary','.beacon-step-builder','.beacon-type-grid','.beacon-type-button','.beacon-compact-list','.beacon-row','.beacon-type-drawer','.beacon-drawer-scrim','.beacon-fallback-block-form','.beacon-home','.beacon-home-top','.beacon-create-flow','.beacon-file-table','.beacon-file-row','.beacon-file-header','.beacon-mini-row','forced-colors'] as $selector)$this->runner->assert(str_contains($beaconCss,$selector),"Beacon builder CSS is missing {$selector}.");
            $journeyCss=(string)file_get_contents(KORAVIK_ROOT.'/public/assets/journey.css');
            foreach(['.route-journey .journey-card','.route-journey .conversation-panel','.route-journey .inline-actions','.route-journey .proposal-demo'] as $selector)$this->runner->assert(str_contains($journeyCss,$selector),"Journey route spacing CSS is missing {$selector}.");
            $worldCss=(string)file_get_contents(KORAVIK_ROOT.'/public/assets/world.css');
            foreach(['.eastern-room-preview','.world-boundary-note','.refuge-choice-list','forced-colors'] as $selector)$this->runner->assert(str_contains($worldCss,$selector),"Epic Ordinary polish CSS is missing {$selector}.");
            $service->markReactionReviewed($account,$reaction);
            $reviewed=$service->dashboard($account);
            $this->runner->assert($reviewed['reactions'][0]['reviewed_at']!==null,'World reaction review state did not persist.');
            try{$service->markReactionReviewed($account,$otherReaction);$denied=false;}catch(\RuntimeException){$denied=true;}
            $this->runner->assert($denied,'World reaction review crossed account ownership.');
            $this->pdo->prepare('INSERT INTO world_choice_history (id,installation_id,scene_key,choice_key,choice_label,created_at) VALUES ("95000000-0000-4000-8000-000000000013",:installation,"eastern-room-purpose","rest","A room for rest",UTC_TIMESTAMP())')->execute(['installation'=>$installation]);
            $chapterThree=new \Koravik\Worlds\EpicOrdinary\ChapterTwoService(\database());
            $chapterThree->beginChapterThree($account);
            $chapterThree->chooseListeningTruth($account,'possibility');
            $chapterState=$chapterThree->home($account);
            $this->runner->assert(($chapterState['current_chapter']??'')==='the-listening-wall'&&($chapterState['current_scene']??'')==='the-wall-remembers','Chapter Three did not advance durable World progress.');
            $this->runner->assert(($chapterState['listening_choice']['choice_key']??'')==='possibility','Chapter Three did not preserve the chosen fictional truth.');
            $chapterThree->chooseListeningTruth($account,'care');
            $this->runner->assert(($chapterThree->home($account)['listening_choice']['choice_key']??'')==='possibility','Chapter Three choice was not idempotent.');
            $freshAccount='95000000-0000-4000-8000-000000000009';
            $this->account($freshAccount,'world-install@test.invalid');
            (new \Koravik\Worlds\WorldService(\database()))->install('epic-ordinary',$freshAccount);
            $initialized=$service->dashboard($freshAccount);
            $this->runner->assert(($initialized['active_world']['current_scene']??'')==='caretaker-welcome','First World install did not initialize a playable scene.');
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id=:account')->execute(['account'=>$freshAccount]);
        } finally {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id IN (:account,:other)')->execute(['account'=>$account,'other'=>$other]);
        }
    }

    private function healingHome(): void
    {
        $account='96000000-0000-4000-8000-000000000001';$other='96000000-0000-4000-8000-000000000002';
        $installation='96000000-0000-4000-8000-000000000003';$otherInstallation='96000000-0000-4000-8000-000000000004';
        $reaction='96000000-0000-4000-8000-000000000005';$otherReaction='96000000-0000-4000-8000-000000000006';
        try {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id IN (:account,:other)')->execute(['account'=>$account,'other'=>$other]);
            $this->account($account,'healing-home@test.invalid');$this->account($other,'healing-home-other@test.invalid');
            $questId=(new QuestService(\database()))->create($account,'Tend the kitchen light','Care for the ordinary center of the day.',['purpose'=>'Keep the room usable and kind.','next_step'=>'Clear the table before dinner.','starts_on'=>gmdate('Y-m-d')]);
            (new QuestService(\database()))->create($other,'Private other Quest','',['starts_on'=>gmdate('Y-m-d')]);
            $installationInsert=$this->pdo->prepare('INSERT INTO world_installations (id,account_id,world_key,status,installed_at) VALUES (:id,:account,"epic-ordinary","active",UTC_TIMESTAMP())');
            $installationInsert->execute(['id'=>$installation,'account'=>$account]);
            $installationInsert->execute(['id'=>$otherInstallation,'account'=>$other]);
            $reactionInsert=$this->pdo->prepare('INSERT INTO world_reactions (id,installation_id,source_event_id,title,message,explanation,source_fact_key,source_fact_summary,rule_key,interpreted_at,created_at) VALUES (:id,:installation,:event,:title,:message,"An approved completion fact matched a World rule.","quest.completed","A Quest occurrence was completed.","home-notices",UTC_TIMESTAMP(),UTC_TIMESTAMP())');
            $reactionInsert->execute(['id'=>$reaction,'installation'=>$installation,'event'=>'96000000-0000-4000-8000-000000000007','title'=>'A lamp warmed the room','message'=>'Mara found the old mark by the mantel.']);
            $reactionInsert->execute(['id'=>$otherReaction,'installation'=>$otherInstallation,'event'=>'96000000-0000-4000-8000-000000000008','title'=>'Other private World change','message'=>'This should stay elsewhere.']);
            $service=new JourneyService(\database());
            $service->homeForAccount($account);
            $home=$service->homeForAccount($account);
            $this->runner->assert(($home['focus_quest']['id']??'')===$questId,'Healing Home did not compose the owned focus Quest.');
            $this->runner->assert(count($home['changes'])===1&&$home['changes'][0]['title']==='A lamp warmed the room','Healing Home exposed the wrong World change.');
            $this->runner->assert(count($home['relationships'])===1&&$home['relationships'][0]['character_key']==='caretaker','Healing Home did not materialize Caretaker continuity.');
            $controller=new \Koravik\Platform\Journey\HealingHomeController(\database());
            $render=(new \ReflectionClass($controller))->getMethod('renderHome');
            $render->setAccessible(true);
            $html=(string)$render->invoke($controller,['id'=>$account,'display_name'=>'Test'],$home);
            foreach(['home-illustration','aria-label="A warm cutaway room','Quest Board','Fireplace','Journal Table','Keepsake Shelf','Companion Chair','Nothing was lost while you were away'] as $needle)$this->runner->assert(str_contains($html,$needle),"Healing Home UI is missing {$needle}.");
            $this->runner->assert($home['state']['last_returned_at']!==null,'Healing Home did not preserve return continuity.');
        } finally {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id IN (:account,:other)')->execute(['account'=>$account,'other'=>$other]);
        }
    }

    private function healingHomeRooms(): void
    {
        $account='97000000-0000-4000-8000-000000000001';
        try {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id=:account')->execute(['account'=>$account]);
            $this->account($account,'healing-room@test.invalid');
            $questId=(new QuestService(\database()))->create($account,'Set out tomorrow clothes','',['purpose'=>'Make morning kinder.','next_step'=>'Put the clothes on the chair.','starts_on'=>gmdate('Y-m-d')]);
            $service=new JourneyService(\database());
            $home=$service->homeForAccount($account);
            $controller=new \Koravik\Platform\Journey\HealingHomeController(\database());
            $renderHome=(new \ReflectionClass($controller))->getMethod('renderHome');
            $renderHome->setAccessible(true);
            $homeHtml=(string)$renderHome->invoke($controller,['id'=>$account,'display_name'=>'Test'],$home);
            foreach(['/home/rooms/quest_board','/home/rooms/journal_table','/home/rooms/companion_chair'] as $needle)$this->runner->assert(str_contains($homeHtml,$needle),"Healing Home overview is missing room link {$needle}.");
            $room=$service->roomForAccount($account,'quest_board');
            $this->runner->assert(($room['focus_quest']['id']??'')===$questId,'Quest Board room did not compose owned Quest.');
            $renderRoom=(new \ReflectionClass($controller))->getMethod('renderRoom');
            $renderRoom->setAccessible(true);
            $roomHtml=(string)$renderRoom->invoke($controller,$room);
            foreach(['Healing Home Room','Open in Quests','Quests owns titles','Return home'] as $needle)$this->runner->assert(str_contains($roomHtml,$needle),"Quest Board room UI is missing {$needle}.");
            $locked=$service->roomForAccount($account,'garden');
            $lockedHtml=(string)$renderRoom->invoke($controller,$locked);
            $this->runner->assert(str_contains($lockedHtml,'has not opened yet')&&str_contains($lockedHtml,'Return home'),'Locked room state is not useful.');
            $this->runner->assert($service->roomForAccount($account,'../../secrets')===null,'Room lookup accepted an invalid room key.');
        } finally {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id=:account')->execute(['account'=>$account]);
        }
    }

    private function healingHomeRestState(): void
    {
        $account='98000000-0000-4000-8000-000000000001';
        try {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id=:account')->execute(['account'=>$account]);
            $this->account($account,'healing-rest@test.invalid');
            $service=new JourneyService(\database());
            $room=$service->roomForAccount($account,'journal_table');
            $this->runner->assert(($room['current_room']??'')==='entry_hall','Opening a room changed rest state without consent.');
            $service->restInRoom($account,'journal_table');
            $rested=$service->roomForAccount($account,'journal_table');
            $this->runner->assert(($rested['current_room']??'')==='journal_table','Rest state did not persist.');
            $audit=$this->pdo->prepare('SELECT COUNT(*) FROM audit_log WHERE account_id=:account AND action="healing_home.room.rested" AND subject_id="journal_table"');
            $audit->execute(['account'=>$account]);
            $this->runner->assert((int)$audit->fetchColumn()===1,'Rest state audit evidence was not recorded.');
            $controller=new \Koravik\Platform\Journey\HealingHomeController(\database());
            $renderRoom=(new \ReflectionClass($controller))->getMethod('renderRoom');
            $renderRoom->setAccessible(true);
            $roomHtml=(string)$renderRoom->invoke($controller,$rested);
            $this->runner->assert(str_contains($roomHtml,'You are resting here.'),'Room detail did not show current rest state.');
            $home=$service->homeForAccount($account);
            $renderHome=(new \ReflectionClass($controller))->getMethod('renderHome');
            $renderHome->setAccessible(true);
            $homeHtml=(string)$renderHome->invoke($controller,['id'=>$account,'display_name'=>'Test'],$home);
            $this->runner->assert(str_contains($homeHtml,'current-room')&&str_contains($homeHtml,'Resting here'),'Healing Home overview did not mark the current room.');
            try{$service->restInRoom($account,'garden');$denied=false;}catch(\RuntimeException){$denied=true;}
            $this->runner->assert($denied,'Rest state accepted a locked room.');
        } finally {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id=:account')->execute(['account'=>$account]);
        }
    }

    private function healingHomeRoomNotes(): void
    {
        $account='99000000-0000-4000-8000-000000000001';
        try {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id=:account')->execute(['account'=>$account]);
            $this->account($account,'healing-note@test.invalid');
            $service=new JourneyService(\database());
            $service->saveRoomNote($account,'journal_table','Remember why this room is calm.');
            $room=$service->roomForAccount($account,'journal_table');
            $this->runner->assert(($room['room']['note_text']??'')==='Remember why this room is calm.','Room note did not persist.');
            $controller=new \Koravik\Platform\Journey\HealingHomeController(\database());
            $renderRoom=(new \ReflectionClass($controller))->getMethod('renderRoom');
            $renderRoom->setAccessible(true);
            $html=(string)$renderRoom->invoke($controller,$room);
            foreach(['Room Note','Not saved to Chronicle','Remember why this room is calm.','Clear note'] as $needle)$this->runner->assert(str_contains($html,$needle),"Room note UI is missing {$needle}.");
            $audit=$this->pdo->prepare('SELECT COUNT(*) FROM audit_log WHERE account_id=:account AND action="healing_home.room_note.saved" AND subject_id="journal_table"');
            $audit->execute(['account'=>$account]);
            $this->runner->assert((int)$audit->fetchColumn()===1,'Room note save audit evidence was not recorded.');
            $exportId=(new AccountDataService(\database()))->requestExport($account,'json');
            $export=(new AccountDataService(\database()))->export($account,$exportId);
            $exportData=json_decode((string)$export['export_json'],true);
            $roomExports=array_values(array_filter($exportData['healing_home_rooms']??[],fn(array $row):bool=>($row['room_key']??'')==='journal_table'));
            $this->runner->assert(count($roomExports)===1&&($roomExports[0]['note_text']??'')==='Remember why this room is calm.','Account export omitted Healing Home room note.');
            try{$service->saveRoomNote($account,'journal_table',str_repeat('x',601));$bounded=false;}catch(\RuntimeException){$bounded=true;}
            $this->runner->assert($bounded,'Room note accepted more than 600 characters.');
            try{$service->saveRoomNote($account,'garden','Sneak into locked room.');$denied=false;}catch(\RuntimeException){$denied=true;}
            $this->runner->assert($denied,'Room note accepted a locked room.');
            $service->clearRoomNote($account,'journal_table');
            $cleared=$service->roomForAccount($account,'journal_table');
            $this->runner->assert(($cleared['room']['note_text']??null)===null,'Room note did not clear.');
        } finally {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id=:account')->execute(['account'=>$account]);
        }
    }

    private function healingHomeEasternRoom(): void
    {
        $account='9a000000-0000-4000-8000-000000000001';$other='9a000000-0000-4000-8000-000000000002';
        $installation='9a000000-0000-4000-8000-000000000003';$otherInstallation='9a000000-0000-4000-8000-000000000004';
        $choice='9a000000-0000-4000-8000-000000000005';$otherChoice='9a000000-0000-4000-8000-000000000006';
        try {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id IN (:account,:other)')->execute(['account'=>$account,'other'=>$other]);
            $this->account($account,'eastern-room@test.invalid');$this->account($other,'eastern-room-other@test.invalid');
            $installationInsert=$this->pdo->prepare('INSERT INTO world_installations (id,account_id,world_key,status,installed_at) VALUES (:id,:account,"epic-ordinary","active",UTC_TIMESTAMP())');
            $installationInsert->execute(['id'=>$installation,'account'=>$account]);
            $installationInsert->execute(['id'=>$otherInstallation,'account'=>$other]);
            $progress=$this->pdo->prepare('INSERT INTO world_narrative_progress (installation_id,current_arc,current_chapter,current_scene,updated_at) VALUES (:installation,"making-refuge","the-eastern-room","room-restored",UTC_TIMESTAMP())');
            $progress->execute(['installation'=>$installation]);$progress->execute(['installation'=>$otherInstallation]);
            $choiceInsert=$this->pdo->prepare('INSERT INTO world_choice_history (id,installation_id,scene_key,choice_key,choice_label,created_at) VALUES (:id,:installation,"eastern-room-purpose",:key,:label,UTC_TIMESTAMP())');
            $choiceInsert->execute(['id'=>$choice,'installation'=>$installation,'key'=>'rest','label'=>'A room for rest']);
            $choiceInsert->execute(['id'=>$otherChoice,'installation'=>$otherInstallation,'key'=>'welcome','label'=>'A room for welcome']);
            $this->pdo->prepare('INSERT INTO world_keepsakes (id,installation_id,keepsake_key,name,description,source_scene,acquired_at) VALUES ("9a000000-0000-4000-8000-000000000007",:installation,"linen-thread","A Linen Thread","A pale thread from the first curtain hung in the restored room.","eastern-room-purpose",UTC_TIMESTAMP())')->execute(['installation'=>$installation]);
            $this->pdo->prepare('INSERT INTO world_keepsakes (id,installation_id,keepsake_key,name,description,source_scene,acquired_at) VALUES ("9a000000-0000-4000-8000-000000000008",:installation,"small-key","A Small Brass Key","This should stay in another account.","eastern-room-purpose",UTC_TIMESTAMP())')->execute(['installation'=>$otherInstallation]);

            $service=new JourneyService(\database());
            $room=$service->roomForAccount($account,'eastern_room');
            $this->runner->assert(($room['room']['state']??'')==='open','Eastern Room did not open after the Chapter Two choice.');
            $this->runner->assert(count($room['changes'])===1&&$room['changes'][0]['title']==='The Eastern Room opened','Eastern Room change was not materialized.');
            $this->runner->assert(count($room['keepsakes'])===1&&$room['keepsakes'][0]['name']==='A Linen Thread','Eastern Room keepsake was missing or crossed account scope.');
            $controller=new \Koravik\Platform\Journey\HealingHomeController(\database());
            $renderRoom=(new \ReflectionClass($controller))->getMethod('renderRoom');
            $renderRoom->setAccessible(true);
            $html=(string)$renderRoom->invoke($controller,$room);
            foreach(['A room with a chosen purpose.','Epic Ordinary owns the chapter choice','A Linen Thread','Continue Epic Ordinary'] as $needle)$this->runner->assert(str_contains($html,$needle),"Eastern Room UI is missing {$needle}.");
        } finally {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id IN (:account,:other)')->execute(['account'=>$account,'other'=>$other]);
        }
    }

    private function healingHomeRelationshipConversations(): void
    {
        $account='9b000000-0000-4000-8000-000000000001';$other='9b000000-0000-4000-8000-000000000002';
        $installation='9b000000-0000-4000-8000-000000000003';$reaction='9b000000-0000-4000-8000-000000000004';
        try {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id IN (:account,:other)')->execute(['account'=>$account,'other'=>$other]);
            $this->account($account,'caretaker-talk@test.invalid');$this->account($other,'caretaker-talk-other@test.invalid');
            $this->pdo->prepare('INSERT INTO world_installations (id,account_id,world_key,status,installed_at) VALUES (:id,:account,"epic-ordinary","active",UTC_TIMESTAMP())')->execute(['id'=>$installation,'account'=>$account]);
            $this->pdo->prepare('INSERT INTO world_reactions (id,installation_id,source_event_id,title,message,explanation,source_fact_key,source_fact_summary,rule_key,interpreted_at,created_at) VALUES (:id,:installation,"9b000000-0000-4000-8000-000000000005","A threshold warmed","The room made space for return.","Only a minimized approved fact was interpreted.","quest.completed","A Quest occurrence was completed.","caretaker-notices",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$reaction,'installation'=>$installation]);
            $service=new JourneyService(\database());
            $service->homeForAccount($account);
            $service->converseWithCaretaker($account,'quiet');
            $relationship=$service->relationshipForAccount($account,'caretaker');
            $this->runner->assert(count($relationship['conversations']??[])===1,'Caretaker conversation did not persist.');
            $this->runner->assert(($relationship['conversations'][0]['player_choice']??'')==='quiet','Caretaker conversation saved the wrong choice.');
            $this->runner->assert(str_contains((string)($relationship['conversations'][0]['remembered_context']??''),'room made space'),'Caretaker conversation did not include bounded remembered context.');
            $otherRelationship=$service->relationshipForAccount($other,'caretaker');
            $this->runner->assert(count($otherRelationship['conversations']??[])===0,'Caretaker conversations crossed account scope.');
            $controller=new \Koravik\Platform\Journey\HealingHomeController(\database());
            $render=(new \ReflectionClass($controller))->getMethod('renderRelationship');
            $render->setAccessible(true);
            $html=(string)$render->invoke($controller,$relationship);
            foreach(['Speak with the Caretaker','Sit quietly','No answer is demanded of you.','does not create a Quest, Chronicle entry, Companion memory, or World fact'] as $needle)$this->runner->assert(str_contains($html,$needle),"Caretaker conversation UI is missing {$needle}.");
            $audit=$this->pdo->prepare('SELECT COUNT(*) FROM audit_log WHERE account_id=:account AND action="healing_home.relationship.conversed" AND subject_id="caretaker"');
            $audit->execute(['account'=>$account]);
            $this->runner->assert((int)$audit->fetchColumn()===1,'Caretaker conversation audit evidence was not recorded.');
            $exportId=(new AccountDataService(\database()))->requestExport($account,'json');
            $export=(new AccountDataService(\database()))->export($account,$exportId);
            $exportData=json_decode((string)$export['export_json'],true);
            $this->runner->assert(count($exportData['relationship_conversations']??[])===1,'Account export omitted relationship conversation.');
            try{$service->converseWithCaretaker($account,'flatter');$denied=false;}catch(\RuntimeException){$denied=true;}
            $this->runner->assert($denied,'Caretaker conversation accepted an invalid choice.');
        } finally {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id IN (:account,:other)')->execute(['account'=>$account,'other'=>$other]);
        }
    }

    private function healingHomeRoomMap(): void
    {
        $account='9c000000-0000-4000-8000-000000000001';$installation='9c000000-0000-4000-8000-000000000002';$choice='9c000000-0000-4000-8000-000000000003';
        try {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id=:account')->execute(['account'=>$account]);
            $this->account($account,'room-map@test.invalid');
            $this->pdo->prepare('INSERT INTO world_installations (id,account_id,world_key,status,installed_at) VALUES (:id,:account,"epic-ordinary","active",UTC_TIMESTAMP())')->execute(['id'=>$installation,'account'=>$account]);
            $this->pdo->prepare('INSERT INTO world_narrative_progress (installation_id,current_arc,current_chapter,current_scene,updated_at) VALUES (:installation,"making-refuge","the-eastern-room","room-restored",UTC_TIMESTAMP())')->execute(['installation'=>$installation]);
            $this->pdo->prepare('INSERT INTO world_choice_history (id,installation_id,scene_key,choice_key,choice_label,created_at) VALUES (:id,:installation,"eastern-room-purpose","making","A room for making",UTC_TIMESTAMP())')->execute(['id'=>$choice,'installation'=>$installation]);
            $service=new JourneyService(\database());
            $service->restInRoom($account,'fireplace');
            $home=$service->homeForAccount($account);
            $controller=new \Koravik\Platform\Journey\HealingHomeController(\database());
            $renderHome=(new \ReflectionClass($controller))->getMethod('renderHome');
            $renderHome->setAccessible(true);
            $html=(string)$renderHome->invoke($controller,['id'=>$account,'display_name'=>'Test'],$home);
            foreach(['Room map','Every room names what it holds','Open room','Door waiting','Restored room open','aria-current="location"','home-room-eastern_room restored-room','Epic Ordinary refuge, opened by Chapter Two.'] as $needle)$this->runner->assert(str_contains($html,$needle),"Healing Home room map is missing {$needle}.");
            $css=(string)file_get_contents(KORAVIK_ROOT.'/public/assets/journey.css');
            foreach(['.home-room-map','.room-map-status','.home-room.restored-room','.home-room.locked a::before'] as $selector)$this->runner->assert(str_contains($css,$selector),"Healing Home room map CSS is missing {$selector}.");
        } finally {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id=:account')->execute(['account'=>$account]);
        }
    }

    private function healingHomeFireplaceReactionDetail(): void
    {
        $account='9d000000-0000-4000-8000-000000000001';$other='9d000000-0000-4000-8000-000000000002';
        $installation='9d000000-0000-4000-8000-000000000003';$otherInstallation='9d000000-0000-4000-8000-000000000004';
        $reaction='9d000000-0000-4000-8000-000000000005';$otherReaction='9d000000-0000-4000-8000-000000000006';
        try {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id IN (:account,:other)')->execute(['account'=>$account,'other'=>$other]);
            $this->account($account,'fireplace-reaction@test.invalid');$this->account($other,'fireplace-reaction-other@test.invalid');
            $installationInsert=$this->pdo->prepare('INSERT INTO world_installations (id,account_id,world_key,status,installed_at) VALUES (:id,:account,"epic-ordinary","active",UTC_TIMESTAMP())');
            $installationInsert->execute(['id'=>$installation,'account'=>$account]);
            $installationInsert->execute(['id'=>$otherInstallation,'account'=>$other]);
            $reactionInsert=$this->pdo->prepare('INSERT INTO world_reactions (id,installation_id,source_event_id,title,message,explanation,source_fact_key,source_fact_summary,rule_key,interpreted_at,created_at) VALUES (:id,:installation,:event,:title,:message,:explanation,"quest.completed",:summary,:rule,UTC_TIMESTAMP(),UTC_TIMESTAMP())');
            $reactionInsert->execute(['id'=>$reaction,'installation'=>$installation,'event'=>'9d000000-0000-4000-8000-000000000007','title'=>'The fire changed color','message'=>'The Caretaker noticed a promise kept.','explanation'=>'Epic Ordinary interpreted a completed Quest occurrence.','summary'=>'A Quest occurrence was completed.','rule'=>'caretaker-notices-completion']);
            $reactionInsert->execute(['id'=>$otherReaction,'installation'=>$otherInstallation,'event'=>'9d000000-0000-4000-8000-000000000008','title'=>'Other private fire','message'=>'This should not render.','explanation'=>'Other account only.','summary'=>'Other account fact.','rule'=>'other-rule']);
            $service=new JourneyService(\database());
            $room=$service->roomForAccount($account,'fireplace');
            $this->runner->assert(count($room['world_reactions']??[])===1&&$room['world_reactions'][0]['id']===$reaction,'Fireplace exposed the wrong World reactions.');
            $controller=new \Koravik\Platform\Journey\HealingHomeController(\database());
            $renderRoom=(new \ReflectionClass($controller))->getMethod('renderRoom');
            $renderRoom->setAccessible(true);
            $html=(string)$renderRoom->invoke($controller,$room);
            foreach(['Why the house noticed','Approved fact','World rule','Deliberately excluded','Quest notes, Chronicle prose','Mark reviewed','caretaker-notices-completion'] as $needle)$this->runner->assert(str_contains($html,$needle),"Fireplace reaction UI is missing {$needle}.");
            (new WorldHomeService(\database()))->markReactionReviewed($account,$reaction);
            $reviewed=$service->roomForAccount($account,'fireplace');
            $reviewedHtml=(string)$renderRoom->invoke($controller,$reviewed);
            $this->runner->assert(str_contains($reviewedHtml,'Reviewed ')&&!str_contains($reviewedHtml,'Other private fire'),'Fireplace reviewed state failed or leaked another reaction.');
        } finally {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id IN (:account,:other)')->execute(['account'=>$account,'other'=>$other]);
        }
    }

    private function healingHomeKeepsakeShelf(): void
    {
        $account='9e000000-0000-4000-8000-000000000001';$other='9e000000-0000-4000-8000-000000000002';
        $keepsake='9e000000-0000-4000-8000-000000000003';$otherKeepsake='9e000000-0000-4000-8000-000000000004';
        try {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id IN (:account,:other)')->execute(['account'=>$account,'other'=>$other]);
            $this->account($account,'keepsake-shelf@test.invalid');$this->account($other,'keepsake-shelf-other@test.invalid');
            $service=new JourneyService(\database());
            $service->homeForAccount($account);$service->homeForAccount($other);
            $this->pdo->prepare('INSERT INTO healing_home_keepsakes (id,account_id,source_type,source_id,keepsake_key,name,meaning,room_key,displayed,created_at) VALUES (:id,:account,"world_choice","9e000000-0000-4000-8000-000000000005","linen-thread","A Linen Thread","A pale thread from the first curtain.","eastern_room",1,UTC_TIMESTAMP())')->execute(['id'=>$keepsake,'account'=>$account]);
            $this->pdo->prepare('INSERT INTO healing_home_keepsakes (id,account_id,source_type,source_id,keepsake_key,name,meaning,room_key,displayed,created_at) VALUES (:id,:account,"quest_resolution","9e000000-0000-4000-8000-000000000006","small-token","Other Token","This should stay elsewhere.","fireplace",1,UTC_TIMESTAMP())')->execute(['id'=>$otherKeepsake,'account'=>$other]);
            $keepsakes=$service->keepsakesForAccount($account);
            $this->runner->assert(count($keepsakes)===1&&$keepsakes[0]['id']===$keepsake,'Keepsake shelf did not stay account-scoped.');
            $controller=new \Koravik\Platform\Journey\HealingHomeController(\database());
            $renderShelf=(new \ReflectionClass($controller))->getMethod('renderKeepsakes');
            $renderShelf->setAccessible(true);
            $shelfHtml=(string)$renderShelf->invoke($controller,$keepsakes);
            foreach(['Keepsake Shelf','Epic Ordinary World choice','Eastern Room','They are not currency, trophies','/home/keepsakes/'.$keepsake] as $needle)$this->runner->assert(str_contains($shelfHtml,$needle),"Keepsake shelf UI is missing {$needle}.");
            $renderKeepsake=(new \ReflectionClass($controller))->getMethod('renderKeepsake');
            $renderKeepsake->setAccessible(true);
            $detail=$service->keepsakeForAccount($account,$keepsake);
            $detailHtml=(string)$renderKeepsake->invoke($controller,$detail);
            foreach(['Provenance','Source owner','Open room','does not create a Quest, Chronicle entry, Companion memory'] as $needle)$this->runner->assert(str_contains($detailHtml,$needle),"Keepsake detail UI is missing {$needle}.");
            $this->runner->assert($service->keepsakeForAccount($account,$otherKeepsake)===null,'Keepsake detail crossed account scope.');
            $emptyHtml=(string)$renderShelf->invoke($controller,[]);
            $this->runner->assert(str_contains($emptyHtml,'No keepsakes are displayed yet'),'Keepsake shelf empty state is missing.');
        } finally {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id IN (:account,:other)')->execute(['account'=>$account,'other'=>$other]);
        }
    }

    private function healingHomeJournalTableReflectionBridge(): void
    {
        $account='9f000000-0000-4000-8000-000000000001';
        try {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id=:account')->execute(['account'=>$account]);
            $this->account($account,'journal-bridge@test.invalid');
            $service=new JourneyService(\database());
            $room=$service->roomForAccount($account,'journal_table');
            $controller=new \Koravik\Platform\Journey\HealingHomeController(\database());
            $renderRoom=(new \ReflectionClass($controller))->getMethod('renderRoom');
            $renderRoom->setAccessible(true);
            $roomHtml=(string)$renderRoom->invoke($controller,$room);
            foreach(['Start a reflection','context=healing_home_journal_table','Chronicle owns the saved entry'] as $needle)$this->runner->assert(str_contains($roomHtml,$needle),"Journal Table bridge is missing {$needle}.");
            $_GET=['context'=>'healing_home_journal_table','title'=>'Journal Table reflection','tags'=>'healing-home,journal-table'];
            $chronicle=new \Koravik\Platform\Experience\ChronicleManagementController(\database());
            $form=(new \ReflectionClass($chronicle))->getMethod('form');
            $form->setAccessible(true);
            ob_start();$form->invoke($chronicle);$formHtml=(string)ob_get_clean();
            $_GET=[];
            foreach(['Started from Healing Home','Journal Table context','value="Journal Table reflection"','value="healing-home,journal-table"','Chronicle owns the saved entry, validation, privacy, archive, and deletion behavior'] as $needle)$this->runner->assert(str_contains($formHtml,$needle),"Chronicle bridge form is missing {$needle}.");
        } finally {
            $_GET=[];
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id=:account')->execute(['account'=>$account]);
        }
    }

    private function healingHomeGardenUnlock(): void
    {
        $account='9f100000-0000-4000-8000-000000000001';
        try {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id=:account')->execute(['account'=>$account]);
            $this->account($account,'garden-unlock@test.invalid');
            $service=new JourneyService(\database());
            $locked=$service->roomForAccount($account,'garden');
            $this->runner->assert(($locked['room']['state']??'')==='visible_locked','Garden opened before relationship conversation.');
            $service->converseWithCaretaker($account,'gratitude');
            $garden=$service->roomForAccount($account,'garden');
            $this->runner->assert(($garden['room']['state']??'')==='open','Garden did not open after Caretaker conversation.');
            $this->runner->assert(count($garden['changes'])===1&&$garden['changes'][0]['title']==='The Garden gate opened','Garden change was not materialized.');
            $controller=new \Koravik\Platform\Journey\HealingHomeController(\database());
            $renderRoom=(new \ReflectionClass($controller))->getMethod('renderRoom');
            $renderRoom->setAccessible(true);
            $html=(string)$renderRoom->invoke($controller,$garden);
            foreach(['A place for tending.','never streaks, punishment, or proof','Reflect from the Garden','Chronicle owns any reflection'] as $needle)$this->runner->assert(str_contains($html,$needle),"Garden room UI is missing {$needle}.");
            $home=$service->homeForAccount($account);
            $renderHome=(new \ReflectionClass($controller))->getMethod('renderHome');
            $renderHome->setAccessible(true);
            $homeHtml=(string)$renderHome->invoke($controller,['id'=>$account,'display_name'=>'Test'],$home);
            $this->runner->assert(str_contains($homeHtml,'home-room open home-room-garden')&&str_contains($homeHtml,'Tending, recovery, and small chosen care.'),'Garden map state did not render as open.');
            $css=(string)file_get_contents(KORAVIK_ROOT.'/public/assets/journey.css');
            $this->runner->assert(str_contains($css,'.garden-room-panel')&&str_contains($css,'.home-room-garden.open'),'Garden CSS contract is missing.');
        } finally {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id=:account')->execute(['account'=>$account]);
        }
    }

    private function healingHomeRoomExpansion(): void
    {
        $account='9f200000-0000-4000-8000-000000000001';$installation='9f200000-0000-4000-8000-000000000002';$reaction='9f200000-0000-4000-8000-000000000003';
        try {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id=:account')->execute(['account'=>$account]);
            $this->account($account,'room-expansion@test.invalid');
            $this->pdo->prepare('INSERT INTO world_installations (id,account_id,world_key,status,installed_at) VALUES (:id,:account,"epic-ordinary","active",UTC_TIMESTAMP())')->execute(['id'=>$installation,'account'=>$account]);
            $this->pdo->prepare('INSERT INTO world_narrative_progress (installation_id,current_arc,current_chapter,current_scene,updated_at) VALUES (:installation,"making-refuge","the-eastern-room","room-restored",UTC_TIMESTAMP())')->execute(['installation'=>$installation]);
            $this->pdo->prepare('INSERT INTO world_choice_history (id,installation_id,scene_key,choice_key,choice_label,created_at) VALUES ("9f200000-0000-4000-8000-000000000004",:installation,"eastern-room-purpose","making","A room for making",UTC_TIMESTAMP())')->execute(['installation'=>$installation]);
            $this->pdo->prepare('INSERT INTO world_reactions (id,installation_id,source_event_id,title,message,explanation,source_fact_key,source_fact_summary,rule_key,interpreted_at,created_at) VALUES (:id,:installation,"9f200000-0000-4000-8000-000000000005","A margin note appeared","The Library had something to explain.","Only minimized fact.","quest.completed","A Quest occurrence was completed.","library-opens",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$reaction,'installation'=>$installation]);
            (new WorldHomeService(\database()))->markReactionReviewed($account,$reaction);
            $service=new JourneyService(\database());
            $service->converseWithCaretaker($account,'repair');
            $service->tendGarden($account,'water');
            $service->saveRoomNote($account,'workshop',"Make without proving\nA tiny sketch is enough.");
            $workshop=$service->roomForAccount($account,'workshop');
            $library=$service->roomForAccount($account,'library');
            $garden=$service->roomForAccount($account,'garden');
            $this->runner->assert(($workshop['room']['state']??'')==='open'&&($library['room']['state']??'')==='open'&&($garden['room']['state']??'')==='open','Expanded rooms did not unlock from their source moments.');
            $controller=new \Koravik\Platform\Journey\HealingHomeController(\database());
            $renderRoom=(new \ReflectionClass($controller))->getMethod('renderRoom');$renderRoom->setAccessible(true);
            $workshopHtml=(string)$renderRoom->invoke($controller,$workshop);
            $libraryHtml=(string)$renderRoom->invoke($controller,$library);
            $gardenHtml=(string)$renderRoom->invoke($controller,$garden);
            foreach(['A place for making and repair.','Intention label','Make without proving','Move through the Healing Home','Next room','Unfinished ideas shelf','Local shelf','Room practice','Leave a seed unfinished'] as $needle)$this->runner->assert(str_contains($workshopHtml,$needle),"Workshop expansion UI is missing {$needle}.");
            foreach(['A place for explanations.','What the house knows','Explanation browser','World reaction shelf','Privacy shelf'] as $needle)$this->runner->assert(str_contains($libraryHtml,$needle),"Library expansion UI is missing {$needle}.");
            foreach(['Water gently','Something was tended','Garden growth state','Watered: yes','not streaks or points'] as $needle)$this->runner->assert(str_contains($gardenHtml,$needle),"Garden tending UI/history is missing {$needle}.");
            $fireplaceHtml=(string)$renderRoom->invoke($controller,$service->roomForAccount($account,'fireplace'));
            foreach(['Narrative center','The Fireplace reads the echoes','Recent echoes gather here'] as $needle)$this->runner->assert(str_contains($fireplaceHtml,$needle),"Fireplace intrigue UI is missing {$needle}.");
            $easternHtml=(string)$renderRoom->invoke($controller,$service->roomForAccount($account,'eastern_room'));
            foreach(['Purpose deepening','A room for making','Trace the source thread'] as $needle)$this->runner->assert(str_contains($easternHtml,$needle),"Eastern Room intrigue UI is missing {$needle}.");
            $service->homeForAccount($account);
            $home=$service->homeForAccount($account);
            $renderHome=(new \ReflectionClass($controller))->getMethod('renderHome');$renderHome->setAccessible(true);
            $homeHtml=(string)$renderHome->invoke($controller,['id'=>$account,'display_name'=>'Test'],$home);
            foreach(['Green Dusk','Return scene','Today in the house','Room directory','Source glossary','What the house knows','Arrival scene','What changed since you were gone','home-blueprint-map','room-symbol','Choose a room by meaning','House pulse','Tended and listening','Lamplight is under the door','House resonance','Choose a path by the kind of care','Open the house guide','Living house','House invitations','Thresholds','House atlas','Room lore','House constellations','Boundary ledger','Wayfinding','House compass','Moods','Rooms by need','Consent map','House changelog','home-command-center','Start here','Trust and meaning','Healing Home is ready to move sections'] as $needle)$this->runner->assert(str_contains($homeHtml,$needle),"Home expansion UI is missing {$needle}.");
            $renderTimeline=(new \ReflectionClass($controller))->getMethod('renderTimeline');$renderTimeline->setAccessible(true);
            $timelineHtml=(string)$renderTimeline->invoke($controller,$service->timelineForAccount($account));
            foreach(['Room timeline','What the house has held','Something was tended','Caretaker conversation'] as $needle)$this->runner->assert(str_contains($timelineHtml,$needle),"Room timeline is missing {$needle}.");
            $source=$service->sourceThreadForAccount($account,'change',$garden['changes'][0]['id']);
            $renderSource=(new \ReflectionClass($controller))->getMethod('renderSourceThread');$renderSource->setAccessible(true);
            $sourceHtml=(string)$renderSource->invoke($controller,$source);
            foreach(['Healing Home source thread','Where this came from','What stayed private','Open room','Follow the thread','Open house guide'] as $needle)$this->runner->assert(str_contains($sourceHtml,$needle),"Source thread UI is missing {$needle}.");
            $renderGuide=(new \ReflectionClass($controller))->getMethod('renderHomeGuide');$renderGuide->setAccessible(true);
            $guideHtml=(string)$renderGuide->invoke($controller);
            foreach(['Healing Home guide','How to move through the house','When you want meaning','When you want boundaries','Threshold reminders','Today in the house'] as $needle)$this->runner->assert(str_contains($guideHtml,$needle),"Home guide UI is missing {$needle}.");
            $renderToday=(new \ReflectionClass($controller))->getMethod('renderHomeToday');$renderToday->setAccessible(true);
            $todayHtml=(string)$renderToday->invoke($controller,$home);
            foreach(['Today in the house','Current room','Latest threshold','Suggested gentle route'] as $needle)$this->runner->assert(str_contains($todayHtml,$needle),"Home today UI is missing {$needle}.");
            $renderDirectory=(new \ReflectionClass($controller))->getMethod('renderRoomDirectory');$renderDirectory->setAccessible(true);
            $directoryHtml=(string)$renderDirectory->invoke($controller,$home);
            foreach(['Room directory','All known rooms','Quest Board','Source-aware purpose'] as $needle)$this->runner->assert(str_contains($directoryHtml,$needle),"Room directory UI is missing {$needle}.");
            $renderGlossary=(new \ReflectionClass($controller))->getMethod('renderSourceGlossary');$renderGlossary->setAccessible(true);
            $glossaryHtml=(string)$renderGlossary->invoke($controller);
            foreach(['Source glossary','Quests','Chronicle','Worlds','Deliberately excluded'] as $needle)$this->runner->assert(str_contains($glossaryHtml,$needle),"Source glossary UI is missing {$needle}.");
            $renderInvitations=(new \ReflectionClass($controller))->getMethod('renderHouseInvitations');$renderInvitations->setAccessible(true);
            $invitationsHtml=(string)$renderInvitations->invoke($controller,$home);
            foreach(['House invitations','Invitations are gentle doorways','Open invitation','Workshop'] as $needle)$this->runner->assert(str_contains($invitationsHtml,$needle),"House invitations UI is missing {$needle}.");
            $renderThresholds=(new \ReflectionClass($controller))->getMethod('renderHouseThresholds');$renderThresholds->setAccessible(true);
            $thresholdsHtml=(string)$renderThresholds->invoke($controller,$home);
            foreach(['Thresholds','Open thresholds','Waiting thresholds','Open doorway'] as $needle)$this->runner->assert(str_contains($thresholdsHtml,$needle),"House thresholds UI is missing {$needle}.");
            $renderAtlas=(new \ReflectionClass($controller))->getMethod('renderHouseAtlas');$renderAtlas->setAccessible(true);
            $atlasHtml=(string)$renderAtlas->invoke($controller);
            foreach(['House atlas','North: Meaning','East: Story','Center: Return'] as $needle)$this->runner->assert(str_contains($atlasHtml,$needle),"House atlas UI is missing {$needle}.");
            $renderLore=(new \ReflectionClass($controller))->getMethod('renderRoomLore');$renderLore->setAccessible(true);
            $loreHtml=(string)$renderLore->invoke($controller);
            foreach(['Room lore','The interpretive hearth','The bench for unfinished things','Visit room'] as $needle)$this->runner->assert(str_contains($loreHtml,$needle),"Room lore UI is missing {$needle}.");
            $renderConstellations=(new \ReflectionClass($controller))->getMethod('renderHouseConstellations');$renderConstellations->setAccessible(true);
            $constellationHtml=(string)$renderConstellations->invoke($controller);
            foreach(['House constellations','Meaning constellation','Making constellation','Welcome constellation'] as $needle)$this->runner->assert(str_contains($constellationHtml,$needle),"House constellations UI is missing {$needle}.");
            $renderLedger=(new \ReflectionClass($controller))->getMethod('renderBoundaryLedger');$renderLedger->setAccessible(true);
            $ledgerHtml=(string)$renderLedger->invoke($controller);
            foreach(['Boundary ledger','May show','Must ask first','Must not touch'] as $needle)$this->runner->assert(str_contains($ledgerHtml,$needle),"Boundary ledger UI is missing {$needle}.");
            $renderWayfinding=(new \ReflectionClass($controller))->getMethod('renderHouseWayfinding');$renderWayfinding->setAccessible(true);
            $wayfindingHtml=(string)$renderWayfinding->invoke($controller);
            foreach(['Wayfinding','I want to understand why something appeared','Start with boundaries'] as $needle)$this->runner->assert(str_contains($wayfindingHtml,$needle),"Wayfinding UI is missing {$needle}.");
            $renderCompass=(new \ReflectionClass($controller))->getMethod('renderHouseCompass');$renderCompass->setAccessible(true);
            $compassHtml=(string)$renderCompass->invoke($controller);
            foreach(['House compass','North / Meaning','Threshold / Trust'] as $needle)$this->runner->assert(str_contains($compassHtml,$needle),"House compass UI is missing {$needle}.");
            $renderMoods=(new \ReflectionClass($controller))->getMethod('renderHouseMoods');$renderMoods->setAccessible(true);
            $moodsHtml=(string)$renderMoods->invoke($controller);
            foreach(['Moods of the house','Quiet morning','Green dusk','Workshop lamplight'] as $needle)$this->runner->assert(str_contains($moodsHtml,$needle),"House moods UI is missing {$needle}.");
            $renderNeeds=(new \ReflectionClass($controller))->getMethod('renderRoomsByNeed');$renderNeeds->setAccessible(true);
            $needsHtml=(string)$renderNeeds->invoke($controller);
            foreach(['Rooms by need','I need clarity','I need safety'] as $needle)$this->runner->assert(str_contains($needsHtml,$needle),"Rooms by need UI is missing {$needle}.");
            $renderConsentMap=(new \ReflectionClass($controller))->getMethod('renderConsentMap');$renderConsentMap->setAccessible(true);
            $consentMapHtml=(string)$renderConsentMap->invoke($controller);
            foreach(['Consent map','Saving','Sharing','Excluded'] as $needle)$this->runner->assert(str_contains($consentMapHtml,$needle),"Consent map UI is missing {$needle}.");
            $renderChangelog=(new \ReflectionClass($controller))->getMethod('renderHouseChangelog');$renderChangelog->setAccessible(true);
            $changelogHtml=(string)$renderChangelog->invoke($controller);
            foreach(['House changelog','Foundation','Compass'] as $needle)$this->runner->assert(str_contains($changelogHtml,$needle),"House changelog UI is missing {$needle}.");
            $renderPrivacy=(new \ReflectionClass($controller))->getMethod('renderHomePrivacy');$renderPrivacy->setAccessible(true);
            $privacyHtml=(string)$renderPrivacy->invoke($controller);
            foreach(['What the house knows','Composed sources','Deliberately not accessed','Quest notes','Data controls'] as $needle)$this->runner->assert(str_contains($privacyHtml,$needle),"Healing Home privacy panel is missing {$needle}.");
            $css=(string)file_get_contents(KORAVIK_ROOT.'/public/assets/journey.css');
            foreach(['.workshop-room-panel','.library-room-panel','.guest-room-panel','.room-intention-label','.home-arrival-scene','.room-walkway','.home-atmosphere-green_dusk','.home-blueprint-map','.home-pulse-panel','.garden-growth-list','.unfinished-idea-card','.source-thread-panel','.home-resonance-routes','.room-practice-panel','.home-guide-grid','.source-thread-actions','.home-today-grid','.room-directory-panel','.source-glossary-grid','.home-threshold-panel','.home-living-house','.room-invitation-panel','.house-invitations-grid','.house-threshold-columns','.house-atlas-map','.room-lore-grid','.house-constellation-grid','.boundary-ledger-grid','.house-wayfinding-grid','.house-compass-grid','.house-moods-grid','.rooms-by-need-grid','.consent-map-grid','.house-changelog-list','.home-command-center','.home-command-grid'] as $selector)$this->runner->assert(str_contains($css,$selector),"Expansion CSS is missing {$selector}.");
        } finally {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id=:account')->execute(['account'=>$account]);
        }
    }

    private function questChroniclePolish(): void
    {
        $index=(string)file_get_contents(KORAVIK_ROOT.'/public/index.php');
        foreach(['quest-polish-panel','Choose the next honest action.','Preserve meaning intentionally.','What Quests owns','chronicle-polish-panel','Preserve what matters, not everything.','chronicle-editor-trust','This creates Chronicle memory only when you choose Save.','What Chronicle owns','action-memory-polish.css'] as $needle)$this->runner->assert(str_contains($index,$needle),"Quest and Chronicle polish composition is missing {$needle}.");
        $css=(string)file_get_contents(KORAVIK_ROOT.'/public/assets/action-memory-polish.css');
        foreach(['.action-memory-loop','.quest-polish-panel','.chronicle-polish-panel','.ownership-bridge-strip','.reflection-bridge-panel','.chronicle-editor-trust','forced-colors'] as $selector)$this->runner->assert(str_contains($css,$selector),"Quest and Chronicle polish CSS is missing {$selector}.");
    }

    private function healthFoundation(): void
    {
        $account='9f300000-0000-4000-8000-000000000001';
        try {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id=:account')->execute(['account'=>$account]);
            $this->account($account,'health-foundation@test.invalid');
            $service=new HealthService(\database());
            $checkin=$service->save($account,[
                'observed_on'=>gmdate('Y-m-d'),
                'energy_level'=>'2',
                'feeling_word'=>'tired',
                'private_note'=>'Private words that must never leave Health.',
                'share_derived_fact'=>'1',
            ]);
            $saved=$service->get($account,$checkin);
            $this->runner->assert($saved['private_note']==='Private words that must never leave Health.','Health did not retain its private note.');
            $statement=$this->pdo->prepare('SELECT payload_json FROM platform_outbox WHERE account_id=:account AND event_name="Health.WellbeingCheckInRecorded" ORDER BY created_at DESC LIMIT 1');
            $statement->execute(['account'=>$account]);
            $payload=(string)$statement->fetchColumn();
            $decoded=json_decode($payload,true);
            $this->runner->assert(($decoded['energy_band']??'')==='low'&&($decoded['observed_on']??'')===gmdate('Y-m-d'),'Health did not publish the approved minimized fact.');
            foreach(['Private words','tired','private_note','feeling_word'] as $excluded)$this->runner->assert(!str_contains($payload,$excluded),"Health event leaked {$excluded}.");
            $service->save($account,['observed_on'=>gmdate('Y-m-d'),'energy_level'=>'4','feeling_word'=>'hopeful','private_note'=>'Corrected privately.'],$checkin);
            $this->runner->assert((int)$service->get($account,$checkin)['energy_level']===4,'Health correction was not persisted.');
            $service->delete($account,$checkin);
            $this->runner->assert($service->history($account)===[],'Health deletion left an owned check-in behind.');
            $statement=$this->pdo->prepare('SELECT COUNT(*) FROM health_checkin_revisions WHERE account_id=:account AND action="deleted" AND checkin_id IS NULL');
            $statement->execute(['account'=>$account]);
            $this->runner->assert((int)$statement->fetchColumn()===1,'Health deletion did not preserve its non-content lifecycle revision.');
        } finally {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id=:account')->execute(['account'=>$account]);
        }
    }

    private function discoveryTrustCampaignFollowup(): void
    {
        foreach([
            'src/Platform/Search/SearchController.php'=>['Gather','Beacon','Health','Notes and feeling words are not exposed'],
            'src/Platform/Notifications/NotificationService.php'=>['gather.followup','beacon.campaigns','health.private'],
            'src/Platform/Privacy/PrivacyController.php'=>['Recipient status','Inspectable audit detail','Approval context'],
            'src/Platform/Privacy/PrivacyService.php'=>['health.wellbeing_band','Companion context','beacon.campaign.updated','gather.followup.created'],
            'src/Platform/Settings/SettingsController.php'=>['Account settings hub','Security and sessions','Data controls','Open full accessibility controls'],
            'src/Worlds/WorldController.php'=>['World catalog','Permission preview','Requested subscriptions','data minimization'],
            'src/Worlds/WorldLifecycleController.php'=>['installed-worlds-management','What these controls never touch','Health records','Beacon pages'],
            'src/Districts/Beacon/BeaconController.php'=>['Create a Beacon campaign','Beacon campaign','privacy-aware engagement','never own RSVP','Beacon Mission Control','Beacon Public Trust Layer'],
            'src/Districts/Beacon/BeaconService.php'=>['beacon_campaigns','createCampaign','updateCampaign'],
            'src/Districts/Gather/GatherController.php'=>['Gather Host Operating System','Gather Event Lifecycle Map','gather-broad-stroke-presence'],
            'src/Districts/Gather/GatherOperationsController.php'=>['Host Mission Control','gather-ops-presence-panel','gather-operations-presence'],
            'src/Districts/Gather/GatherLifecycleController.php'=>['Personal Plan Layer','Front Desk Layer','Aftercare Proposal Layer'],
            'src/Districts/Gather/GatherCloseoutController.php'=>['Draft post-event follow-up','gather_event_followups','created_chronicle_proposal','gather.followup.created'],
            'src/Districts/Health/HealthController.php'=>['Private 30-day trend','Record detail and revision path','not diagnosis'],
            'src/Districts/Health/HealthService.php'=>['trends','health_checkin_revisions'],
        ] as $file=>$needles){
            $source=(string)file_get_contents(KORAVIK_ROOT.'/'.$file);
            foreach($needles as $needle)$this->runner->assert(str_contains($source,$needle),"{$file} is missing {$needle}.");
        }
        $handoff=(string)file_get_contents(KORAVIK_ROOT.'/docs/IMPLEMENTATION_HANDOFF.md');
        foreach(['Gather + Beacon Massive Presence Pass','Gather Host Operating System','Beacon Mission Control','Beacon Public Presence Layer'] as $needle)$this->runner->assert(str_contains($handoff,$needle),"Implementation handoff is missing {$needle}.");
        foreach(['GLOBAL_SEARCH_COMPLETION','NOTIFICATIONS_CENTER_COMPLETION','PRIVACY_CONSENT_CENTER','AUDIT_ACTIVITY_DETAIL','ACCOUNT_SETTINGS_HUB','WORLD_CATALOG_PERMISSION_PREVIEW','INSTALLED_WORLDS_MANAGEMENT_POLISH','BEACON_CAMPAIGNS','GATHER_HOST_FOLLOWUP','HEALTH_RECORD_DETAIL_TRENDS'] as $doc)$this->runner->assert(is_file(KORAVIK_ROOT.'/docs/features/'.$doc.'.md'),"Missing feature doc {$doc}.");
    }

    private function layoutRecurrenceMediaAdmin(): void
    {
        foreach([
            'src/Platform/Hearth/HearthLayoutController.php'=>['hearth-customization-completion','Layout preview','Restore defaults'],
            'src/Platform/Hearth/HearthLayoutService.php'=>['organizations','households','trust'],
            'src/Districts/Quests/LivingQuestController.php'=>['Quest recurrence editor','Plain-language preview','/recurrence'],
            'src/Districts/Quests/QuestService.php'=>['updateRecurrence','quest.recurrence.updated'],
            'src/Application.php'=>['quest-completion-confirmation','World eligibility','bounded undo window'],
            'src/Platform/Companion/CompanionContextController.php'=>['Companion memory controls','Correct memory','Disable future use'],
            'src/Platform/Companion/CompanionContextService.php'=>['updateMemory','companion.memory.corrected'],
            'src/Platform/Experience/ChronicleManagementController.php'=>['Proposed reflection review','Save intentionally to Chronicle','Dismiss proposal'],
            'src/Platform/Experience/ChronicleManagementService.php'=>['chronicle_reflection_reviews','saveReflectionProposal','dismissReflectionProposal'],
            'src/Districts/Gather/GatherController.php'=>['gather-calendar-list','gather-event-detail-completion','Calendar/list view'],
            'src/Districts/Beacon/BeaconController.php'=>['Beacon public page blocks','Add block','beacon-public-block'],
            'src/Districts/Beacon/BeaconService.php'=>['beacon_page_blocks','addBlock'],
            'src/Platform/Media/MediaController.php'=>['media-foundation','Platform Media','Add media reference'],
            'src/Platform/Admin/SystemHealthController.php'=>['system-health-admin','Build 217','No secrets'],
            'public/index.php'=>['SystemHealthController','MediaController','durable-cross-module-drafts'],
        ] as $file=>$needles){
            $source=(string)file_get_contents(KORAVIK_ROOT.'/'.$file);
            foreach($needles as $needle)$this->runner->assert(str_contains($source,$needle),"{$file} is missing {$needle}.");
        }
        foreach(['HEARTH_CUSTOMIZATION_COMPLETION','QUEST_RECURRENCE_EDITOR','QUEST_COMPLETION_CONFIRMATION_UNDO','COMPANION_MEMORY_CONTROLS','PROPOSED_REFLECTION_REVIEW','GATHER_EVENT_DETAIL_COMPLETION','GATHER_CALENDAR_LIST_VIEW','BEACON_PUBLIC_PAGE_BLOCKS','PLATFORM_MEDIA_FOUNDATION','SYSTEM_HEALTH_ADMINISTRATION'] as $doc)$this->runner->assert(is_file(KORAVIK_ROOT.'/docs/features/'.$doc.'.md'),"Missing feature doc {$doc}.");
    }

    private function builds138147(): void
    {
        foreach([
            'database/migrations/102_runtime_schema_compatibility.sql'=>['gather_event_followups','beacon_campaigns','utf8mb4_0900_ai_ci'],
            'src/Platform/Admin/SystemHealthController.php'=>['admin-release-readiness-console','Runtime schema compatibility','Collation / UUID Join Audit','Worker / Mail Queue Operations Console'],
            'src/Platform/Hearth/HearthLayoutService.php'=>['hearth-source-aware-widget','Organizations','Households','Trust and recovery'],
            'src/Platform/Notifications/NotificationController.php'=>['Open source','Why did I receive this?','Mark all read'],
            'src/Platform/Media/MediaService.php'=>['Chronicle','Beacon','Gather','Health'],
            'docs/IMPLEMENTATION_HANDOFF.md'=>['Builds 138–147','Build 147','102_runtime_schema_compatibility.sql'],
        ] as $file=>$needles){
            $source=(string)file_get_contents(KORAVIK_ROOT.'/'.$file);
            foreach($needles as $needle)$this->runner->assert(str_contains($source,$needle),"{$file} is missing {$needle}.");
        }
        $doc=KORAVIK_ROOT.'/docs/features/BUILDS_138_147_RUNTIME_COHERENCE_FOUNDATION.md';
        $this->runner->assert(is_file($doc),'Missing Builds 138–147 feature contract.');
        $source=(string)file_get_contents($doc);
        foreach(['Runtime Schema Compatibility Hardening','Hearth Source-Aware Widget Rendering','Admin Release Readiness Console','Notification Sync Safety Pass'] as $needle)$this->runner->assert(str_contains($source,$needle),"Builds 138–147 contract is missing {$needle}.");
        $expected=[
            'gather_event_followups.event_id'=>'utf8mb4_0900_ai_ci',
            'beacon_campaigns.page_id'=>'utf8mb4_unicode_ci',
            'beacon_campaigns.short_link_id'=>'utf8mb4_0900_ai_ci',
        ];
        foreach($expected as $column=>$collation){
            [$table,$name]=explode('.',$column,2);
            $s=$this->pdo->prepare('SELECT COLLATION_NAME FROM information_schema.COLUMNS WHERE table_schema=DATABASE() AND table_name=:table AND column_name=:column');
            $s->execute(['table'=>$table,'column'=>$name]);
            $this->runner->assert((string)$s->fetchColumn()===$collation,"{$column} is not aligned for runtime joins.");
        }
    }

    private function builds148157(): void
    {
        foreach([
            'database/migrations/103_core_loop_media_timeline.sql'=>['platform_media_links','quest_timeline_events'],
            'src/Platform/Media/MediaController.php'=>['media-attachments-district-records','Attach media reference','Quests','Chronicle','Gather','Beacon','Health'],
            'src/Platform/Media/MediaService.php'=>['platform_media_links','linksFor','Choose one of your media references'],
            'src/Districts/Quests/QuestService.php'=>['Quest Recurrence Occurrence Rebuild','quest_timeline_events','timelineFor','Daily Focus + Quest Completion Loop Polish'],
            'src/Districts/Quests/LivingQuestController.php'=>['/timeline','quest-detail-timeline','Creation, edits, recurrence changes, completions, reversals, pauses, resumes, and reflections'],
            'src/Platform/Experience/ChronicleManagementController.php'=>['chronicle-reflection-proposal-sources','chronicle-entry-search-filters','Quest completion','Gather follow-up','Healing Home Journal'],
            'src/Platform/Experience/ChronicleManagementService.php'=>['proposeFromSource','source_module','entry_type'],
            'src/Platform/Companion/CompanionContextController.php'=>['companion-memory-provenance-detail','Open provenance detail','Usage boundary'],
            'src/Platform/Companion/CompanionController.php'=>['companion-suggestion-action-review-polish','Exact destination','approval is version-specific'],
            'src/Districts/Health/HealthService.php'=>['hearthSignal','No feeling words or private notes leave Health'],
            'src/Platform/Hearth/HearthLayoutService.php'=>['health-to-hearth-private-signal-summary','Private Health summary'],
            'src/Worlds/EpicOrdinary/WorldProgressController.php'=>['worlds-reaction-explanation-polish','Permission and review state'],
            'src/Worlds/EpicOrdinary/WorldProgressService.php'=>['permission_state','review_state','world_reaction_reviews'],
            'docs/IMPLEMENTATION_HANDOFF.md'=>['Builds 148–157','103_core_loop_media_timeline.sql','core-loop depth'],
        ] as $file=>$needles){
            $source=(string)file_get_contents(KORAVIK_ROOT.'/'.$file);
            foreach($needles as $needle)$this->runner->assert(str_contains($source,$needle),"{$file} is missing {$needle}.");
        }
        $doc=KORAVIK_ROOT.'/docs/features/BUILDS_148_157_CORE_LOOP_DEPTH.md';
        $this->runner->assert(is_file($doc),'Missing Builds 148–157 feature contract.');
        $source=(string)file_get_contents($doc);
        foreach(['Media Attachments in District Records','Quest Recurrence Occurrence Rebuild','Chronicle Entry Search + Filters','Worlds Reaction Explanation Polish'] as $needle)$this->runner->assert(str_contains($source,$needle),"Builds 148–157 contract is missing {$needle}.");
    }

    private function builds158167(): void
    {
        foreach([
            'src/Platform/Notifications/NotificationController.php'=>['Open source','Why did I receive this?','Dismiss','Mark all read'],
            'src/Districts/Beacon/BeaconController.php'=>['unified-public-preview-safety','Move up','Move down','Beacon page block reordering + publishing checks'],
            'src/Districts/Beacon/BeaconService.php'=>['Publishing checks require','moveBlock','block_reordered'],
            'src/Districts/Gather/GatherController.php'=>['gather-public-event-preview','Gather Public Event Preview','gather-participant-communication-preferences','Participant communication preferences'],
            'src/Platform/AccountData/AccountDataController.php'=>['account-data-export-review','Account Data Export Review','account-closure-consequence-preview','Account Closure Consequence Preview'],
            'src/Platform/AccountData/AccountDataService.php'=>['review','cancellation_window','source modules delete'],
            'src/Platform/Admin/SystemHealthController.php'=>['admin-release-readiness-console','worker-mail-queue-operations-console','public preview safety'],
            'src/Platform/Mail/MailOperationsController.php'=>['Platform Mail','Recover stale claims','redacted diagnostics'],
            'docs/IMPLEMENTATION_HANDOFF.md'=>['Builds 158–167','public trust and admin polish','Build 167'],
        ] as $file=>$needles){
            $source=(string)file_get_contents(KORAVIK_ROOT.'/'.$file);
            foreach($needles as $needle)$this->runner->assert(str_contains($source,$needle),"{$file} is missing {$needle}.");
        }
        $doc=KORAVIK_ROOT.'/docs/features/BUILDS_158_167_PUBLIC_TRUST_ADMIN_POLISH.md';
        $this->runner->assert(is_file($doc),'Missing Builds 158–167 feature contract.');
        $source=(string)file_get_contents($doc);
        foreach(['Notification Inbox Actionability','Unified Public Preview Safety','Account Data Export Review','Cross-Surface Empty State Polish'] as $needle)$this->runner->assert(str_contains($source,$needle),"Builds 158–167 contract is missing {$needle}.");
    }

    private function builds168177(): void
    {
        foreach([
            'src/Platform/Orientation/OrientationController.php'=>['first-run-guided-setup','Companion permissions','unified-empty-state-guide-cards'],
            'src/Platform/ReturnExperience/ReturnController.php'=>['returning-user-orientation-upgrade','safe to ignore','one manageable next step'],
            'public/index.php'=>['hearth-today-command-strip','Today’s Focus','pending reflection proposal','Unread notifications','everyday-coherence-navigation'],
            'src/Platform/UI/VisualSystem.php'=>['cross-module-breadcrumbs','route-level-error-recovery-polish','Return to Hearth','appendClass','/gather','/beacon','/health'],
            'src/Platform/UI/GuideController.php'=>['guide-help-center-completion','Act','Reflect','Share','Coordinate','Privacy','Troubleshooting','source-ownership-explainer'],
            'src/Platform/Settings/SettingsController.php'=>['settings-navigation-polish','Profile','System and admin'],
            'src/Platform/UI/AppShell.php'=>['mobile-navigation-pass','nav-toggle','shell-navigation" hidden'],
            'src/Platform/Admin/SystemHealthController.php'=>['Build 217','durable-cross-module-drafts','route-level error recovery polish'],
            'docs/IMPLEMENTATION_HANDOFF.md'=>['Builds 168–177','everyday-coherence-navigation','Build 177'],
        ] as $file=>$needles){
            $source=(string)file_get_contents(KORAVIK_ROOT.'/'.$file);
            foreach($needles as $needle)$this->runner->assert(str_contains($source,$needle),"{$file} is missing {$needle}.");
        }
        $doc=KORAVIK_ROOT.'/docs/features/BUILDS_168_177_ONBOARDING_NAVIGATION_COHERENCE.md';
        $this->runner->assert(is_file($doc),'Missing Builds 168–177 feature contract.');
        $source=(string)file_get_contents($doc);
        foreach(['First-Run Guided Setup','Hearth Today Command Strip','Source Ownership Explainer','Route-Level Error Recovery Polish'] as $needle)$this->runner->assert(str_contains($source,$needle),"Builds 168–177 contract is missing {$needle}.");
    }

    private function builds178187(): void
    {
        foreach([
            'src/Platform/Journey/HealingHomeController.php'=>['healing-home-composition-depth','Health Garden','Gather Table','healing-home-source-matrix','Health Garden is a doorway','Gather Table does not publish','Companion room-note consent'],
            'src/Platform/Search/SearchService.php'=>['home_notes','healing_home_rooms','note_text'],
            'src/Platform/Search/SearchController.php'=>['healing-home-room-note-search','Healing Home room notes','Room notes stay in Healing Home'],
            'src/Platform/Companion/CompanionContextService.php'=>['healing_home.room_notes','Healing Home'],
            'src/Platform/Companion/CompanionContextController.php'=>['companion-healing-home-context-consent','Healing Home room notes','do not authorize background scanning'],
            'src/Platform/Admin/SystemHealthController.php'=>['Build 217','durable-cross-module-drafts','Healing Home composition depth'],
            'public/index.php'=>['197','healing-home-composition-depth'],
            'docs/IMPLEMENTATION_HANDOFF.md'=>['Builds 178–187','healing-home-composition-depth','Build 187'],
        ] as $file=>$needles){
            $source=(string)file_get_contents(KORAVIK_ROOT.'/'.$file);
            foreach($needles as $needle)$this->runner->assert(str_contains($source,$needle),"{$file} is missing {$needle}.");
        }
        $doc=KORAVIK_ROOT.'/docs/features/BUILDS_178_187_HEALING_HOME_COMPOSITION_DEPTH.md';
        $this->runner->assert(is_file($doc),'Missing Builds 178–187 feature contract.');
        $source=(string)file_get_contents($doc);
        foreach(['Health Garden','Gather Table','Room Note Search','Companion Room-Note Consent'] as $needle)$this->runner->assert(str_contains($source,$needle),"Builds 178–187 contract is missing {$needle}.");
    }

    private function builds188197(): void
    {
        foreach([
            'src/Platform/SourceReview/SourceReviewController.php'=>['hearth-source-inbox','source-draft-review-center','Decision Consequence Preview','healing-home-room-note-promotion','gather-followup-draft-bridge','What changes','What does not','Who owns the result'],
            'src/Platform/SourceReview/SourceReviewService.php'=>['chronicle_reflection_reviews','companion_proposals','gather_outcome_proposals','gather_event_followups','healing_home_rooms','notifications'],
            'src/Application.php'=>['quest-from-anywhere-draft-bridge','origin_type','origin_reference','Who owns the result'],
            'src/Platform/Experience/ChronicleManagementController.php'=>['chronicle-from-anywhere-reflection-bridge','body=(string)','Who owns the result'],
            'src/Platform/Companion/CompanionController.php'=>['companion-proposal-routing-upgrade','Open Source Inbox'],
            'src/Districts/Gather/GatherCloseoutController.php'=>['Gather Follow-Up to Quest/Chronicle Drafts','Open Source Inbox'],
            'public/index.php'=>['hearth-today-decision-strip-upgrade','Source Inbox','actionable-cross-module-flow'],
            'src/Platform/Admin/SystemHealthController.php'=>['Build 217','durable-cross-module-drafts','Source Inbox maturity coverage'],
            'docs/IMPLEMENTATION_HANDOFF.md'=>['Builds 188–197','actionable-cross-module-flow','Build 197'],
        ] as $file=>$needles){
            $source=(string)file_get_contents(KORAVIK_ROOT.'/'.$file);
            foreach($needles as $needle)$this->runner->assert(str_contains($source,$needle),"{$file} is missing {$needle}.");
        }
        $doc=KORAVIK_ROOT.'/docs/features/BUILDS_188_197_ACTIONABLE_CROSS_MODULE_FLOW.md';
        $this->runner->assert(is_file($doc),'Missing Builds 188–197 feature contract.');
        $source=(string)file_get_contents($doc);
        foreach(['Hearth Source Inbox','Quest-from-Anywhere Draft Bridge','Chronicle-from-Anywhere Reflection Bridge','Release Checkpoint + Audit Coverage'] as $needle)$this->runner->assert(str_contains($source,$needle),"Builds 188–197 contract is missing {$needle}.");
    }

    private function builds198207(): void
    {
        foreach([
            'src/Platform/SourceReview/SourceReviewController.php'=>['source-inbox-maturity','source-inbox-summary-strip','source-inbox-filter-bar','source-inbox-top-priority','source-inbox-resume-later','source-inbox-empty-state','Resume token'],
            'src/Platform/SourceReview/SourceReviewService.php'=>['summary','owner_key','resume_token','needs_decision','draft_paths','notices'],
            'public/index.php'=>['source-inbox-hearth-badge','source-inbox-maturity','Gather decisions','Companion decisions'],
            'src/Platform/Admin/SystemHealthController.php'=>['Build 217','durable-cross-module-drafts','filters, badges, resume-later affordances'],
            'docs/IMPLEMENTATION_HANDOFF.md'=>['Builds 198–207','source-inbox-maturity','Build 207'],
        ] as $file=>$needles){
            $source=(string)file_get_contents(KORAVIK_ROOT.'/'.$file);
            foreach($needles as $needle)$this->runner->assert(str_contains($source,$needle),"{$file} is missing {$needle}.");
        }
        $doc=KORAVIK_ROOT.'/docs/features/BUILDS_198_207_SOURCE_INBOX_MATURITY.md';
        $this->runner->assert(is_file($doc),'Missing Builds 198–207 feature contract.');
        $source=(string)file_get_contents($doc);
        foreach(['Source Inbox Counts','Source Owner Filters','Top Priority Card','Resume Later Affordance','Hearth Source Inbox Badge'] as $needle)$this->runner->assert(str_contains($source,$needle),"Builds 198–207 contract is missing {$needle}.");
    }

    private function builds208217(): void
    {
        foreach([
            'src/Platform/SourceReview/SourceReviewController.php'=>['Durable Cross-Module Draft','Save durable draft','source_review.room_note','source_review.gather_followup','draft-provenance-timeline','Resume destination review'],
            'src/Platform/Settings/SettingsController.php'=>['moment-controls-preferences','Moment controls and preferences','one arrival at a time'],
            'src/Platform/Experience/ChronicleManagementController.php'=>['chronicle-memory-weaving','Chronicle Memory Weaving','post-save navigation'],
            'src/Districts/Quests/LivingQuestController.php'=>['Quest Momentum Dashboard','source-originated commitments','productivity pressure'],
            'src/Districts/Gather/GatherCloseoutController.php'=>['gather-aftercare-loop','Gather Aftercare Loop','Moment preservation'],
            'src/Platform/Companion/CompanionController.php'=>['companion-trust-boundaries-pass','Companion Trust and Boundaries Pass','what it did not use'],
            'src/Worlds/WorldController.php'=>['world-progress-continuity-pass','World Progress Continuity Pass','room evidence'],
            'src/Platform/ReturnExperience/ReturnController.php'=>['homecoming-return-experience-upgrade','Homecoming / Return Experience Upgrade','one gentle re-entry'],
            'src/Platform/Privacy/PrivacyController.php'=>['cross-module-privacy-audit-surface','Cross-Module Privacy Audit Surface','What never crossed modules'],
            'src/Platform/SourceReview/SourceReviewService.php'=>['platform_form_drafts','durable draft','expires_at','destination still requires explicit review'],
            'src/Platform/Resilience/ResilienceService.php'=>['draft(string $accountId,string $id)','safePayload','expires_at>UTC_TIMESTAMP()'],
            'src/Platform/Resilience/ResilienceController.php'=>['Resume Source Review draft','source_review.','Expires'],
            'public/index.php'=>['217','durable-cross-module-drafts','previous_build'],
            'src/Platform/Admin/SystemHealthController.php'=>['Build 217','durable-cross-module-drafts','draft provenance timeline'],
            'docs/IMPLEMENTATION_HANDOFF.md'=>['Builds 208–217','durable-cross-module-drafts','Build 217','Continuity Controls Loop','Moment Controls and Preferences','Cross-Module Privacy Audit Surface'],
        ] as $file=>$needles){
            $source=(string)file_get_contents(KORAVIK_ROOT.'/'.$file);
            foreach($needles as $needle)$this->runner->assert(str_contains($source,$needle),"{$file} is missing {$needle}.");
        }
        $doc=KORAVIK_ROOT.'/docs/features/BUILDS_208_217_DURABLE_CROSS_MODULE_DRAFTS.md';
        $this->runner->assert(is_file($doc),'Missing Builds 208–217 feature contract.');
        $source=(string)file_get_contents($doc);
        foreach(['Durable Source Review Draft Save','Durable Source Review Draft Resume','Recovery Center Integration','Draft Provenance Timeline'] as $needle)$this->runner->assert(str_contains($source,$needle),"Builds 208–217 contract is missing {$needle}.");
    }

    private function epicOrdinaryReclamation(): void
    {
        foreach([
            'src/Platform/Journey/JourneyService.php'=>['materializeEpicReclamation','Caretaker’s brass lantern','Quiet Hearth whispers','Evidence, not rewards','Nothing important happens off-screen'],
            'src/Platform/Journey/HealingHomeController.php'=>['epic-ordinary-reclamation-sprint','/home/reclamation','/home/discoveries','/home/tiny-joys','/home/seasons','/home/moments','Chronicle integration'],
            'docs/features/EPIC_ORDINARY_RECLAMATION_SPRINT.md'=>['Healing Home reclamation','Reuse ledger','/Applications/MAMP/htdocs/Epic-Ordinary','Tiny Joys','Moments Remembered'],
            'docs/reclamation/EPIC_ORDINARY_RECLAMATION_AUDIT.md'=>['Reclamation audit','NORTH_STAR.md','CANON.md','THE_BOOK_OF_MOMENTS.md','MOMENT_ENGINE.md'],
            'docs/IMPLEMENTATION_HANDOFF.md'=>['Epic Ordinary Reclamation Sprint','reclamation hearth','evidence, not rewards'],
        ] as $file=>$needles){
            $source=(string)file_get_contents(KORAVIK_ROOT.'/'.$file);
            foreach($needles as $needle)$this->runner->assert(str_contains($source,$needle),"{$file} is missing {$needle}.");
        }
    }

    private function momentEngineFoundation(): void
    {
        $account='af100000-0000-4000-8000-000000000001';$installation='af100000-0000-4000-8000-000000000002';$reaction='af100000-0000-4000-8000-000000000003';
        try {
            $this->pdo->prepare('DELETE FROM gather_outcome_proposals WHERE id="af100000-0000-4000-8000-000000000012" OR event_id="af100000-0000-4000-8000-000000000011"')->execute();
            $this->pdo->prepare('DELETE FROM gather_events WHERE id="af100000-0000-4000-8000-000000000011"')->execute();
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id=:account')->execute(['account'=>$account]);
            $this->account($account,'moment-engine@test.invalid');
            $this->pdo->prepare('INSERT INTO world_installations (id,account_id,world_key,status,installed_at) VALUES (:id,:account,"epic-ordinary","active",UTC_TIMESTAMP())')->execute(['id'=>$installation,'account'=>$account]);
            $this->pdo->prepare('INSERT INTO world_reactions (id,installation_id,source_event_id,title,message,explanation,source_fact_key,source_fact_summary,rule_key,interpreted_at,created_at) VALUES (:id,:installation,"af100000-0000-4000-8000-000000000004","The kettle began to sing","Steam curled toward the window before anyone asked it to.","A minimized approved fact became a fictional arrival Moment.","quest.completed","A Quest occurrence was completed.","moment-engine-foundation",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$reaction,'installation'=>$installation]);
            $moments=new \Koravik\Platform\Moments\MomentService(\database());
            $next=$moments->next($account);
            $this->runner->assert(($next['source_module']??'')==='Worlds'&&($next['visibility']??'')==='arrival'&&($next['status']??'')==='queued','Moment Engine did not queue one arrival scene from a World reaction.');
            $this->runner->assert(str_contains((string)$next['excluded_summary'],'Quest notes')&&str_contains((string)$next['provenance_summary'],'Quest occurrence'),'Moment provenance did not preserve privacy boundaries.');
            $moments->archive($account,(string)$next['id']);
            $remembered=$moments->remembered($account);
            $this->runner->assert(count($remembered)===1&&$remembered[0]['status']==='archived','Moment archive did not become replay-safe remembered state.');
            $proposal=$moments->proposeChronicle($account,(string)$next['id']);
            $statement=$this->pdo->prepare('SELECT source_module,source_reference,status,title,body FROM chronicle_reflection_reviews WHERE id=:id AND account_id=:account');
            $statement->execute(['id'=>$proposal,'account'=>$account]);
            $review=$statement->fetch();
            $this->runner->assert(($review['source_module']??'')==='Moment Engine'&&($review['source_reference']??'')===$next['id']&&($review['status']??'')==='proposed','Moment did not create explicit Chronicle review.');
            $this->runner->assert(str_contains((string)($review['body']??''),'Moment review context')&&str_contains((string)($review['body']??''),'Provenance:')&&str_contains((string)($review['title']??''),'Moment:'),'Moment Chronicle proposal did not include review context.');
            $conversationId='af100000-0000-4000-8000-000000000005';
            $keepsakeId='af100000-0000-4000-8000-000000000006';
            $this->pdo->prepare('INSERT INTO relationship_conversations (id,account_id,character_key,conversation_type,prompt_key,player_choice,character_response,remembered_context,created_at) VALUES (:id,:account,"caretaker","check_in","moment-scene","quiet","The Caretaker sets the lantern by the door. There is no rush.","A bounded remembered context.",UTC_TIMESTAMP())')->execute(['id'=>$conversationId,'account'=>$account]);
            $this->pdo->prepare('INSERT INTO healing_home_keepsakes (id,account_id,source_type,source_id,keepsake_key,name,meaning,room_key,displayed,created_at) VALUES (:id,:account,"epic_reclamation",:source,"lantern","Caretaker Lantern","A warm sign that the Home was ready before you arrived.","entry_hall",1,UTC_TIMESTAMP())')->execute(['id'=>$keepsakeId,'account'=>$account,'source'=>$installation]);
            $all=$moments->all($account);
            $templates=array_column($all,'scene_template');
            $this->runner->assert(in_array('caretaker',$templates,true)&&in_array('memory',$templates,true)&&in_array('room',$templates,true),'Moment scene templates did not expand across Caretaker, memory, and room scenes.');
            $caretaker=array_values(array_filter($all,static fn(array $m):bool=>(($m['source_type']??'')==='relationship_conversation')))[0]??[];
            $this->runner->assert(($caretaker['speaker_label']??'')==='The Caretaker'&&($caretaker['primary_object']??'')==='brass lantern','Caretaker scene did not preserve speaker and primary object.');
            $controller=new \Koravik\Platform\Journey\HealingHomeController(\database());
            $detail=['id'=>$keepsakeId,'source_type'=>'epic_reclamation','source_id'=>$installation,'keepsake_key'=>'lantern','name'=>'Caretaker Lantern','meaning'=>'A warm sign that the Home was ready before you arrived.','room_key'=>'entry_hall','displayed'=>1,'created_at'=>gmdate('Y-m-d H:i:s')];
            $renderKeepsake=(new \ReflectionClass($controller))->getMethod('renderKeepsake');$renderKeepsake->setAccessible(true);
            $keepsakeHtml=(string)$renderKeepsake->invoke($controller,$detail);
            foreach(['Prepare as memory Moment','Moment Engine presentation state only','Chronicle still requires explicit review'] as $needle)$this->runner->assert(str_contains($keepsakeHtml,$needle),"Keepsake Moment interaction is missing {$needle}.");
            $this->pdo->prepare('INSERT INTO healing_home_keepsakes (id,account_id,source_type,source_id,keepsake_key,name,meaning,room_key,displayed,created_at) VALUES ("af100000-0000-4000-8000-000000000007",:account,"epic_reclamation",:source,"robin-feather","Robin feather on the sill","A visitor left a trace without becoming a chore.","garden",1,UTC_TIMESTAMP())')->execute(['account'=>$account,'source'=>$installation]);
            $expanded=$moments->all($account);
            $companion=array_values(array_filter($expanded,static fn(array $m):bool=>(($m['scene_template']??'')==='companion')&&(($m['source_id']??'')==='af100000-0000-4000-8000-000000000007')))[0]??[];
            $companionObjectMatches=((string)($companion['primary_object']??''))==='Robin feather on the sill';
            $companionDetailMatches=str_contains((string)($companion['ambient_detail']??''),'visitor trace');
            $this->runner->assert($companionObjectMatches&&$companionDetailMatches,'Companion-ready visitor trace did not seed from a robin keepsake.');
            $questId='af100000-0000-4000-8000-000000000008';$occurrenceId='af100000-0000-4000-8000-000000000009';$resolutionId='af100000-0000-4000-8000-000000000010';$gatherId='af100000-0000-4000-8000-000000000011';$gatherOutcomeId='af100000-0000-4000-8000-000000000012';$healthId='af100000-0000-4000-8000-000000000013';$draftId='af100000-0000-4000-8000-000000000014';$companionProposalId='af100000-0000-4000-8000-000000000015';
            $this->pdo->prepare('INSERT INTO quests (id,account_id,title,description,purpose,quest_type,lifecycle_status,status,created_at,updated_at) VALUES (:id,:account,"Carry soup to a neighbor","A small ordinary kindness.","Keep the ordinary world porous.","action","active","active",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$questId,'account'=>$account]);
            $this->pdo->prepare('INSERT INTO quest_occurrences (id,quest_id,account_id,scheduled_for,status,available_at,completed_at,created_at,updated_at) VALUES (:id,:quest,:account,CURDATE(),"completed",UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$occurrenceId,'quest'=>$questId,'account'=>$account]);
            $this->pdo->prepare('INSERT INTO quest_resolutions (id,quest_id,account_id,outcome,reflection,resolved_at,created_at) VALUES (:id,:quest,:account,"paused","The soup can wait without becoming failure.",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$resolutionId,'quest'=>$questId,'account'=>$account]);
            $this->pdo->prepare('INSERT INTO gather_events (id,account_id,title,description,venue,starts_at,timezone,visibility,status,lifecycle_status,closed_at,closeout_note,created_at,updated_at) VALUES (:id,:account,"Porch supper","Neighbors brought chairs.","Front porch",UTC_TIMESTAMP(),"UTC","unlisted","completed","completed",UTC_TIMESTAMP(),"Everyone left fed.",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$gatherId,'account'=>$account]);
            $this->pdo->prepare('INSERT INTO gather_outcome_proposals (id,event_id,account_id,outcome_type,summary,minimized_payload_json,status,application_status,created_at,updated_at) VALUES (:id,:event,:account,"chronicle_reflection","The porch felt less like a threshold.",JSON_OBJECT("event_id",:event_json),"proposed","not_ready",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$gatherOutcomeId,'event'=>$gatherId,'event_json'=>$gatherId,'account'=>$account]);
            $this->pdo->prepare('INSERT INTO health_wellbeing_checkins (id,account_id,observed_on,energy_level,feeling_word,private_note,share_derived_fact,created_at,updated_at) VALUES (:id,:account,CURDATE(),3,"steady","private health text must stay private",1,UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$healthId,'account'=>$account]);
            $this->pdo->prepare('INSERT INTO platform_form_drafts (id,account_id,form_key,payload_json,expires_at,created_at,updated_at) VALUES (:id,:account,"source_review.loop",JSON_OBJECT("title","Review the porch supper"),DATE_ADD(UTC_TIMESTAMP(),INTERVAL 1 DAY),UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$draftId,'account'=>$account]);
            $this->pdo->prepare('INSERT INTO companion_proposals (id,account_id,proposal_type,status,version,request_text,title,proposed_payload_json,reasoning,source_context,owning_module,consequence,expires_at,created_at,updated_at) VALUES (:id,:account,"quest.create","awaiting_approval",1,"Help me begin gently.","A gentle beginning",JSON_OBJECT("title","A gentle beginning"),"A small first step can be reviewed.","Your request to Companion.","Quests","Nothing executes without approval.",DATE_ADD(UTC_TIMESTAMP(),INTERVAL 1 DAY),UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$companionProposalId,'account'=>$account]);
            $this->pdo->prepare('INSERT INTO world_narrative_progress (installation_id,current_arc,current_chapter,current_scene,updated_at) VALUES (:installation,"making-refuge","the-listening-wall","library-echo",UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE current_arc=VALUES(current_arc),current_chapter=VALUES(current_chapter),current_scene=VALUES(current_scene),updated_at=UTC_TIMESTAMP()')->execute(['installation'=>$installation]);
            $looped=$moments->all($account);$modules=array_values(array_unique(array_column($looped,'source_module')));$types=array_column($looped,'source_type');
            foreach(['Quests','Gather','Health','Source Review','Companion','Worlds'] as $module)$this->runner->assert(in_array($module,$modules,true),"Moment Expansion Loop did not seed {$module}.");
            foreach(['quest_completion','quest_resolution','event_closeout','outcome_proposal','consented_derived_trend','durable_draft','proposal_trace','chapter_progress'] as $type)$this->runner->assert(in_array($type,$types,true),"Moment Expansion Loop is missing {$type}.");
            foreach([
                'database/migrations/104_moment_engine_foundation.sql'=>['platform_moments','queued','presented','archived','chronicle_proposal_id'],
                'database/migrations/105_moment_scene_templates_source_expansion.sql'=>['scene_template','speaker_label','primary_object','ambient_detail','recommended_action_label'],
                'src/Platform/Moments/MomentService.php'=>['submit(string $accountId, array $moment)','seedForAccount','one arrival Moment','proposeChronicle','Moment review context','companion-ready visitor trace Moment','seedQuestMoments','seedGatherMoments','seedHealthMoments','seedSourceReviewMoments','seedCompanionMoments','seedWorldProgressMoments','copyPack'],
                'src/Platform/Moments/MomentController.php'=>['/moments/next','/moments/remembered','Moment Engine Foundation','Prepare Chronicle review','One arrival Moment at a time','Moment Scene Template','Caretaker scenes','Memory object','living-moment-presentation-polish','moment-scene-filter-nav','remembered-moment-actions','Moment Source Review Console','Moment Inbox / Tuning Controls','moment-library-polish','moments.css'],
                'public/assets/moments.css'=>['.moment-stage','.moment-template-caretaker','.moment-template-companion','.moment-source-review-console','.moment-inbox-tuning-controls','prefers-reduced-motion','forced-colors'],
                'public/index.php'=>['MomentController'],
                'src/Platform/Journey/HealingHomeController.php'=>['/moments/next','/moments/remembered','Moment Engine Foundation','Prepare as memory Moment','keepsake-detail-memory-scene'],
                'docs/features/MOMENT_ENGINE_FOUNDATION.md'=>['Moment Engine Foundation','arrival scenes','remembered moments','Chronicle preservation review','Scene Templates and Source Expansion Pass','Chronicle Preservation Polish','Living Moment Presentation Polish','Remembered Moment Actions and Companion-ready Trace Expansion','Moment Expansion Loop','Additional District Moment Submissions','Authored Scene Copy Packs','Moment Library Polish'],
                'docs/IMPLEMENTATION_HANDOFF.md'=>['Moment Engine Foundation','platform_moments','one-at-a-time arrival scenes','Moment Scene Templates and Source Expansion','Living Moment Presentation Polish','Remembered Moment Actions and Companion-ready Trace Expansion','Moment Expansion Loop','Quest-to-Moment Loop','Gather-to-Moment Loop','Companion Presence Moment Layer'],
            ] as $file=>$needles){
                $source=(string)file_get_contents(KORAVIK_ROOT.'/'.$file);
                foreach($needles as $needle)$this->runner->assert(str_contains($source,$needle),"{$file} is missing {$needle}.");
            }
        } finally {
            $this->pdo->prepare('DELETE FROM gather_outcome_proposals WHERE id="af100000-0000-4000-8000-000000000012" OR event_id="af100000-0000-4000-8000-000000000011"')->execute();
            $this->pdo->prepare('DELETE FROM gather_events WHERE id="af100000-0000-4000-8000-000000000011"')->execute();
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id=:account')->execute(['account'=>$account]);
        }
    }

    private function account(string $id,string $email):void{$this->pdo->prepare('INSERT INTO platform_accounts (id,email,display_name,role,status,created_at,updated_at) VALUES (:id,:email,"Test","user","active",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$id,'email'=>$email]);}
    private function http(string $path):array{$url=rtrim((string)\env('APP_URL',''),'/').$path;$context=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);$body=(string)file_get_contents($url,false,$context);$status=0;foreach($http_response_header??[] as $header)if(preg_match('#^HTTP/\S+\s+(\d+)#',$header,$m))$status=(int)$m[1];return[$status,$body];}
}
