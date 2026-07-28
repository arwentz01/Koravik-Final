<?php

declare(strict_types=1);

namespace Koravik\Platform\Hearth;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;

final class HearthLayoutController
{
    public function __construct(private readonly Database $database) {}

    public function handle(string $method,string $path): bool
    {
        if($path!=='/hearth/customize' && $path!=='/hearth/customize/reset') return false;
        $account=Security::account();
        if(!$account) return false;
        $service=new HearthLayoutService($this->database);
        if($method==='POST') {
            if(!Security::verifyCsrf(isset($_POST['csrf'])?(string)$_POST['csrf']:null)) throw new RuntimeException('Your session changed. Please try again.');
            try {
                if($path==='/hearth/customize/reset') { $service->reset((string)$account['id']); $_SESSION['flash']='Hearth layout restored to defaults.'; }
                else { $service->save((string)$account['id'],$_POST); $_SESSION['flash']='Hearth layout saved.'; }
            } catch(RuntimeException $e) { $_SESSION['flash']=$e->getMessage(); }
            header('Location: /hearth/customize',true,303); return true;
        }
        if($method!=='GET' || $path!=='/hearth/customize') return false;
        $this->render($service->get((string)$account['id'])); return true;
    }

    private function render(array $layout): void
    {
        $flash=isset($_SESSION['flash'])?(string)$_SESSION['flash']:'';unset($_SESSION['flash']);
        $rows='';
        foreach($layout as $row) {
            $key=(string)$row['widget_key'];
            $label=HearthLayoutService::WIDGETS[$key];
            $rows.='<li class="settings-card"><input type="hidden" name="order[]" value="'.self::e($key).'"><div><strong>'.self::e($label).'</strong><p class="meta">Optional supporting section. Source records remain owned by their module.</p></div><label class="check-row"><input type="checkbox" name="visible[]" value="'.self::e($key).'"'.((bool)$row['visible']?' checked':'').'> Show on Hearth</label><div class="inline-actions"><button class="quiet-button" name="move" value="'.self::e($key).':up" type="submit" formaction="/hearth/customize">Move up</button><button class="quiet-button" name="move" value="'.self::e($key).':down" type="submit" formaction="/hearth/customize">Move down</button></div></li>';
        }
        $preview='<section class="panel"><h2>Preview</h2><p>The greeting and “What matters now” remain required. Your optional sections will appear in the order shown above.</p></section>';
        $body=($flash!==''?'<div class="notice" role="status">'.self::e($flash).'</div>':'').'<section class="page-heading"><div><p class="eyebrow">Hearth customization</p><h1>What should appear on your Hearth?</h1><p>Keep the center useful, bounded, and yours.</p></div><a class="button secondary" href="/hearth">Preview on Hearth</a></section><form method="post" action="/hearth/customize"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><ol class="preference-list">'.$rows.'</ol><div class="form-actions"><button class="button" type="submit">Save layout</button></div></form><form method="post" action="/hearth/customize/reset"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><button class="button secondary" type="submit">Restore defaults</button></form>'.$preview;
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Customize Hearth · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><a class="skip-link" href="#main">Skip to content</a><header class="app-header"><a class="brand" href="/hearth">Koravik</a><nav aria-label="Primary"><a href="/hearth" aria-current="page">Hearth</a><a href="/quests">Quests</a><a href="/chronicle">Chronicle</a><a href="/worlds">Worlds</a><a href="/search">Search</a><a href="/notifications">Notifications</a><a href="/privacy">Privacy</a><a href="/settings">Settings</a></nav></header><main id="main" class="page" tabindex="-1">'.$body.'</main><footer>Koravik helps you act, then get back to living.</footer></body></html>';
    }
    private static function e(string $v): string { return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
}