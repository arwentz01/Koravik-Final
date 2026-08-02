<?php

declare(strict_types=1);

namespace Koravik\Platform\Settings;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;

final class SettingsController
{
    public function __construct(private readonly Database $database) {}

    public function handle(string $method,string $path): bool
    {
        if($path!=='/settings') return false;
        $account=Security::account();
        if(!$account) return false;
        $service=new SettingsService($this->database);
        if($method==='POST') {
            if(!Security::verifyCsrf(isset($_POST['csrf'])?(string)$_POST['csrf']:null)) throw new RuntimeException('Your session changed. Please try again.');
            try { $service->save((string)$account['id'],$_POST); $_SESSION['account']['display_name']=trim((string)$_POST['display_name']); $_SESSION['flash']='Settings saved.'; }
            catch(RuntimeException $e) { $_SESSION['flash']=$e->getMessage(); }
            header('Location: /settings',true,303); return true;
        }
        if($method!=='GET') return false;
        $this->render($service->get((string)$account['id'])); return true;
    }

    private function render(array $s): void
    {
        $flash=isset($_SESSION['flash'])?(string)$_SESSION['flash']:'';unset($_SESSION['flash']);
        $option=static fn(string $value,string $current,string $label): string=>'<option value="'.self::e($value).'"'.($value===$current?' selected':'').'>'.self::e($label).'</option>';
        $body=($flash!==''?'<div class="notice" role="status">'.self::e($flash).'</div>':'').'<section class="page-heading settings-hub settings-navigation-polish"><div><p class="eyebrow">Account settings hub · Settings Navigation Polish</p><h1>How should Koravik work for you?</h1><p>Profile, security, accessibility, notifications, privacy, data controls, and authorized admin tools are grouped by consequence.</p></div></section><form class="settings-sections" method="post" action="/settings"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><section class="settings-card"><h2>Profile</h2><label>Display name<input name="display_name" maxlength="100" required value="'.self::e((string)$s['display_name']).'"></label></section><section class="settings-card moment-controls-preferences"><h2>Moment controls and preferences</h2><p>Moment intensity starts quiet: one arrival at a time, source types grouped, Chronicle suggestions explicit, and Health/Companion details excluded unless their owner surface says otherwise.</p><p><a href="/moments">Review Moment sources</a> · <a href="/moments/remembered">Open remembered Moments</a></p></section><section class="settings-card"><h2>Appearance and accessibility</h2><label>Appearance<select name="appearance">'.$option('system',(string)$s['appearance'],'Use device setting').$option('light',(string)$s['appearance'],'Light').$option('dark',(string)$s['appearance'],'Dark').'</select></label><label class="check-row"><input type="checkbox" name="reduced_motion"'.((bool)$s['reduced_motion']?' checked':'').'> Reduce nonessential motion</label><label class="check-row"><input type="checkbox" name="high_contrast"'.((bool)$s['high_contrast']?' checked':'').'> Increase interface contrast</label><p><a href="/settings/accessibility">Open full accessibility controls</a></p></section><section class="settings-card"><h2>Time and dates</h2><label>Time zone<select name="timezone">'.$option('America/New_York',(string)$s['timezone'],'Eastern Time').$option('America/Chicago',(string)$s['timezone'],'Central Time').$option('America/Denver',(string)$s['timezone'],'Mountain Time').$option('America/Los_Angeles',(string)$s['timezone'],'Pacific Time').$option('UTC',(string)$s['timezone'],'UTC').'</select></label><label>Date format<select name="date_format">'.$option('month_day_year',(string)$s['date_format'],'Month / day / year').$option('year_month_day',(string)$s['date_format'],'Year / month / day').$option('day_month_year',(string)$s['date_format'],'Day / month / year').'</select></label></section><section class="settings-card"><h2>Notifications and privacy</h2><p><a href="/notifications/preferences">Notification preferences</a></p><p><a href="/privacy">Privacy and consent</a></p><p><a href="/audit">Audit activity</a></p></section><section class="settings-card"><h2>Security and sessions</h2><p><a href="/settings/security">Password settings</a></p><p><a href="/settings/sessions">Signed-in devices</a></p><p><a href="/recovery-center">Recovery center</a></p></section><section class="settings-card"><h2>Data controls</h2><p>Your District records remain owned by their source modules. World permissions can be revoked without deleting source history.</p><p>Account export and staged account closure are available from <a href="/settings/data">Data controls</a>.</p></section><section class="settings-card"><h2>System and admin</h2><p>Authorized operators can review release readiness and mail queues without payload bodies.</p><p><a href="/system/health">System health</a> · <a href="/system/mail">Platform Mail</a></p></section><button class="button" type="submit">Save settings</button></form>';
        echo '<!doctype html><html lang="en" data-appearance="'.self::e((string)$s['appearance']).'" data-contrast="'.((bool)$s['high_contrast']?'high':'standard').'" data-motion="'.((bool)$s['reduced_motion']?'reduced':'standard').'"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Settings · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><a class="skip-link" href="#main">Skip to content</a><header class="app-header"><a class="brand" href="/hearth">Koravik</a><nav aria-label="Primary"><a href="/hearth">Hearth</a><a href="/quests">Quests</a><a href="/chronicle">Chronicle</a><a href="/worlds">Worlds</a><a href="/search">Search</a><a href="/notifications">Notifications</a><a href="/privacy">Privacy</a><a href="/settings" aria-current="page">Settings</a></nav></header><main id="main" class="page" tabindex="-1">'.$body.'</main><footer>Koravik helps you act, then get back to living.</footer></body></html>';
    }
    private static function e(string $v): string { return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
}
