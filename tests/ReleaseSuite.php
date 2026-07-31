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
        $this->runner->test('critical schema matches Builds 068-087', fn() => $this->schema());
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
        foreach (['organization_teams','organization_quest_proposals','organization_recovery_records','households','household_memberships','household_quest_proposals','household_resources','household_recovery_records','platform_form_drafts','platform_idempotency_keys','auth_sessions','world_reaction_reviews'] as $table) {
            $statement = $this->pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=:table');
            $statement->execute(['table'=>$table]);
            $this->runner->assert((int)$statement->fetchColumn() === 1, "Missing table {$table}.");
        }
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
        $this->runner->assert($status===200 && ($payload['status']??'')==='ok' && ($payload['build']??'')==='117' && ($payload['slice']??'')==='healing-home-room-expansion', 'Health checkpoint does not identify the current slice.');
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
            $reactionInsert=$this->pdo->prepare('INSERT INTO world_reactions (id,installation_id,source_event_id,title,message,explanation,source_fact_key,source_fact_summary,rule_key,interpreted_at,created_at) VALUES (:id,:installation,:event,"The house noticed","A light returned.","An approved completion fact matched a World rule.","quest.completed","A Quest occurrence was completed.","caretaker-notices",UTC_TIMESTAMP(),UTC_TIMESTAMP())');
            $reactionInsert->execute(['id'=>$reaction,'installation'=>$installation,'event'=>'95000000-0000-4000-8000-000000000007']);
            $reactionInsert->execute(['id'=>$otherReaction,'installation'=>$otherInstallation,'event'=>'95000000-0000-4000-8000-000000000008']);
            $service=new WorldHomeService(\database());$dashboard=$service->dashboard($account);
            $this->runner->assert(($dashboard['active_world']['world_key']??'')==='epic-ordinary','Worlds Home did not compose the active World.');
            $this->runner->assert(count($dashboard['reactions'])===1&&$dashboard['reactions'][0]['id']===$reaction,'Worlds Home exposed another account’s reaction.');
            $html=(new WorldHomeView())->render($dashboard);
            foreach(['Continue story','Why did this change?','Mark reviewed','fictional World State'] as $needle)$this->runner->assert(str_contains($html,$needle),"Worlds Home UI is missing {$needle}.");
            $service->markReactionReviewed($account,$reaction);
            $reviewed=$service->dashboard($account);
            $this->runner->assert($reviewed['reactions'][0]['reviewed_at']!==null,'World reaction review state did not persist.');
            try{$service->markReactionReviewed($account,$otherReaction);$denied=false;}catch(\RuntimeException){$denied=true;}
            $this->runner->assert($denied,'World reaction review crossed account ownership.');
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
            foreach(['A place for making and repair.','Intention label','Make without proving'] as $needle)$this->runner->assert(str_contains($workshopHtml,$needle),"Workshop expansion UI is missing {$needle}.");
            foreach(['A place for explanations.','What the house knows'] as $needle)$this->runner->assert(str_contains($libraryHtml,$needle),"Library expansion UI is missing {$needle}.");
            foreach(['Water gently','Something was tended'] as $needle)$this->runner->assert(str_contains($gardenHtml,$needle),"Garden tending UI/history is missing {$needle}.");
            $service->homeForAccount($account);
            $home=$service->homeForAccount($account);
            $renderHome=(new \ReflectionClass($controller))->getMethod('renderHome');$renderHome->setAccessible(true);
            $homeHtml=(string)$renderHome->invoke($controller,['id'=>$account,'display_name'=>'Test'],$home);
            foreach(['Green Dusk','Return scene','Room timeline','What the house knows'] as $needle)$this->runner->assert(str_contains($homeHtml,$needle),"Home expansion UI is missing {$needle}.");
            $renderTimeline=(new \ReflectionClass($controller))->getMethod('renderTimeline');$renderTimeline->setAccessible(true);
            $timelineHtml=(string)$renderTimeline->invoke($controller,$service->timelineForAccount($account));
            foreach(['Room timeline','What the house has held','Something was tended','Caretaker conversation'] as $needle)$this->runner->assert(str_contains($timelineHtml,$needle),"Room timeline is missing {$needle}.");
            $renderPrivacy=(new \ReflectionClass($controller))->getMethod('renderHomePrivacy');$renderPrivacy->setAccessible(true);
            $privacyHtml=(string)$renderPrivacy->invoke($controller);
            foreach(['What the house knows','Composed sources','Deliberately not accessed','Quest notes','Data controls'] as $needle)$this->runner->assert(str_contains($privacyHtml,$needle),"Healing Home privacy panel is missing {$needle}.");
            $css=(string)file_get_contents(KORAVIK_ROOT.'/public/assets/journey.css');
            foreach(['.workshop-room-panel','.library-room-panel','.guest-room-panel','.room-intention-label'] as $selector)$this->runner->assert(str_contains($css,$selector),"Expansion CSS is missing {$selector}.");
        } finally {
            $this->pdo->prepare('DELETE FROM platform_accounts WHERE id=:account')->execute(['account'=>$account]);
        }
    }

    private function account(string $id,string $email):void{$this->pdo->prepare('INSERT INTO platform_accounts (id,email,display_name,role,status,created_at,updated_at) VALUES (:id,:email,"Test","user","active",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$id,'email'=>$email]);}
    private function http(string $path):array{$url=rtrim((string)\env('APP_URL',''),'/').$path;$context=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);$body=(string)file_get_contents($url,false,$context);$status=0;foreach($http_response_header??[] as $header)if(preg_match('#^HTTP/\S+\s+(\d+)#',$header,$m))$status=(int)$m[1];return[$status,$body];}
}
