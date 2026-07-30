<?php

declare(strict_types=1);

namespace Koravik\Tests;

use Koravik\Platform\Households\HouseholdService;
use Koravik\Platform\Organizations\OrganizationService;
use Koravik\Platform\Security\Security;
use Koravik\Platform\Settings\AccessibilityService;
use Koravik\Platform\Resilience\FormErrors;
use Koravik\Platform\Resilience\ResilienceService;
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
        $this->runner->test('health identifies Build 117', fn() => $this->health());
        $this->runner->test('mail and recovery operations are present', fn() => $this->operations());
        $this->runner->test('workers remain explicitly bounded', fn() => $this->workers());
        $this->runner->test('accessibility preferences persist, validate, and reset', fn() => $this->accessibility());
        $this->runner->test('workflow recovery is bounded and duplicate-safe', fn() => $this->resilience());
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
        foreach (['organization_teams','organization_quest_proposals','organization_recovery_records','households','household_memberships','household_quest_proposals','household_resources','household_recovery_records','platform_form_drafts','platform_idempotency_keys','auth_sessions'] as $table) {
            $statement = $this->pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=:table');
            $statement->execute(['table'=>$table]);
            $this->runner->assert((int)$statement->fetchColumn() === 1, "Missing table {$table}.");
        }
        $type = (string)$this->pdo->query("SELECT COLUMN_TYPE FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='gather_events' AND column_name='owner_type'")->fetchColumn();
        $this->runner->assert(str_contains($type, "'organization'") && str_contains($type, "'household'"), 'Gather ownership contexts are incomplete.');
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
        $this->runner->assert($status===200 && ($payload['status']??'')==='ok' && ($payload['build']??'')==='117', 'Health checkpoint is not Build 117.');
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

    private function account(string $id,string $email):void{$this->pdo->prepare('INSERT INTO platform_accounts (id,email,display_name,role,status,created_at,updated_at) VALUES (:id,:email,"Test","user","active",UTC_TIMESTAMP(),UTC_TIMESTAMP())')->execute(['id'=>$id,'email'=>$email]);}
    private function http(string $path):array{$url=rtrim((string)\env('APP_URL',''),'/').$path;$context=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>10]]);$body=(string)file_get_contents($url,false,$context);$status=0;foreach($http_response_header??[] as $header)if(preg_match('#^HTTP/\S+\s+(\d+)#',$header,$m))$status=(int)$m[1];return[$status,$body];}
}
