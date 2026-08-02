<?php

declare(strict_types=1);

namespace Koravik\Platform\Admin;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;

final class SystemHealthController
{
    public function __construct(private readonly Database $database) {}

    public function handle(string $method,string $path): bool
    {
        if($method!=='GET'||$path!=='/system/health')return false;
        $account=Security::account();if(!$account)return false;
        if(!in_array((string)($account['role']??''),['owner','admin'],true)){http_response_code(403);echo '<!doctype html><html><body><main class="page"><section class="empty-state"><h1>System health requires admin access.</h1></section></main></body></html>';return true;}
        $this->render();
        return true;
    }

    private function render(): void
    {
        $pdo=$this->database->pdo();
        $migrations=(int)$pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn();
        $outbox=$pdo->query('SELECT status,COUNT(*) count FROM platform_outbox GROUP BY status')->fetchAll();
        $mail=$pdo->query('SELECT status,COUNT(*) count FROM platform_mail_deliveries GROUP BY status')->fetchAll();
        $failed=(int)$pdo->query('SELECT COUNT(*) FROM platform_outbox WHERE status IN ("failed","dead")')->fetchColumn();
        $storage=is_writable(KORAVIK_ROOT.'/public')?'Writable public root':'Public root is read-only';
        $collations=$pdo->query("SELECT TABLE_NAME,COLUMN_NAME,COLLATION_NAME FROM information_schema.COLUMNS WHERE table_schema=DATABASE() AND TABLE_NAME IN ('gather_events','gather_event_followups','beacon_pages','beacon_short_links','beacon_campaigns','hearth_layout_preferences') AND COLUMN_NAME IN ('id','event_id','page_id','short_link_id','widget_key') ORDER BY TABLE_NAME,COLUMN_NAME")->fetchAll();
        $rows='';foreach($outbox as $r)$rows.='<li>Outbox '.self::e((string)$r['status']).': '.(int)$r['count'].'</li>';foreach($mail as $r)$rows.='<li>Mail '.self::e((string)$r['status']).': '.(int)$r['count'].'</li>';
        $collationRows='';foreach($collations as $c)$collationRows.='<li>'.self::e((string)$c['TABLE_NAME']).'.'.self::e((string)$c['COLUMN_NAME']).' · '.self::e((string)($c['COLLATION_NAME']??'not textual')).'</li>';
        $body='<section class="page-heading system-health-admin admin-release-readiness-console"><div><p class="eyebrow">System health</p><h1>Is Koravik operating safely?</h1><p>Operational state only. No secrets, credentials, private notes, or payload bodies are shown.</p></div></section><section class="grid"><article class="surface"><h2>Migrations</h2><p>'.(int)$migrations.' applied migrations.</p></article><article class="surface"><h2>Version</h2><p>Build 217 · durable-cross-module-drafts</p></article><article class="surface"><h2>Failed jobs</h2><p>'.(int)$failed.' failed outbox jobs.</p></article><article class="surface"><h2>Storage</h2><p>'.self::e($storage).'</p></article></section><section class="surface worker-mail-queue-operations-console"><h2>Worker and mail queue operations</h2><p>Worker / Mail Queue Operations Console: finite queues, delivery states, and failed work are summarized without payload bodies.</p><ul>'.($rows?:'<li>No queued work found.</li>').'</ul><p><a href="/system/mail">Open Platform Mail operations</a></p></section><section class="surface"><h2>Runtime schema compatibility</h2><p>Collation / UUID Join Audit shows join-sensitive columns so release checks can catch mixed-collation risk before a click does.</p><ul>'.($collationRows?:'<li>No watched textual columns found.</li>').'</ul></section><section class="surface"><h2>Release readiness</h2><p>Admin Release Readiness Console combines migrations, workers, mail, storage, config posture, public preview safety, account-data previews, route-level error recovery polish, Healing Home composition depth, actionable cross-module flow, Source Inbox maturity coverage, durable cross-module drafts, draft provenance timeline, Recovery Center resume, filters, badges, resume-later affordances, and recent fatal-risk diagnostics in one place.</p></section>';
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>System health · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><main id="main" class="page">'.$body.'</main></body></html>';
    }
    private static function e(string $v): string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
