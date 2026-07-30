<?php

declare(strict_types=1);

namespace Koravik\Platform\Settings;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;

final class AccessibilityController
{
    public function __construct(private readonly Database $database) {}

    public function handle(string $method, string $path): bool
    {
        if (!in_array($path, ['/settings/accessibility', '/settings/accessibility/reset'], true)) return false;
        $account = Security::account();
        if (!$account) return false;
        $service = new AccessibilityService($this->database);
        if ($method === 'POST') {
            if (!Security::verifyCsrf(isset($_POST['csrf']) ? (string)$_POST['csrf'] : null)) {
                throw new RuntimeException('Your session changed. Please try again.');
            }
            try {
                $path === '/settings/accessibility/reset'
                    ? $service->reset((string)$account['id'])
                    : $service->save((string)$account['id'], $_POST);
                $_SESSION['flash'] = $path === '/settings/accessibility/reset'
                    ? 'Accessibility preferences restored.'
                    : 'Accessibility preferences saved.';
            } catch (RuntimeException $exception) {
                $_SESSION['flash'] = $exception->getMessage();
            }
            header('Location: ' . \app_with_base_path('/settings/accessibility'), true, 303);
            return true;
        }
        if ($method !== 'GET' || $path !== '/settings/accessibility') return false;
        $this->render($service->get((string)$account['id']));
        return true;
    }

    private function render(array $settings): void
    {
        $flash = isset($_SESSION['flash']) ? (string)$_SESSION['flash'] : '';
        unset($_SESSION['flash']);
        $checked = static fn(string $key): string => (int)$settings[$key] === 1 ? ' checked' : '';
        $option = static fn(string $value, string $current, string $label): string =>
            '<option value="'.self::e($value).'"'.($value === $current ? ' selected' : '').'>'.self::e($label).'</option>';
        $csrf = self::e(Security::csrfToken());
        $body = ($flash !== '' ? '<div class="notice" role="status">'.self::e($flash).'</div>' : '')
            .'<section class="page-heading"><div><p class="eyebrow">Settings · Accessibility</p><h1>Make Koravik easier to read and navigate.</h1><p>These preferences follow your account across signed-in pages. Browser and device accessibility settings still take priority where they are stronger.</p></div></section>'
            .'<div class="accessibility-layout"><form class="settings-sections accessibility-form" method="post" action="/settings/accessibility">'
            .'<input type="hidden" name="csrf" value="'.$csrf.'">'
            .'<section class="settings-card"><h2>Reading</h2>'
            .'<label>Text size<select name="text_scale">'.$option('standard',(string)$settings['text_scale'],'Standard').$option('large',(string)$settings['text_scale'],'Large (112.5%)').$option('larger',(string)$settings['text_scale'],'Larger (125%)').'</select></label>'
            .'<label>Typeface<select name="typeface">'.$option('system',(string)$settings['typeface'],'System typeface').$option('readable',(string)$settings['typeface'],'Readable sans serif').'</select></label>'
            .'<label>Content spacing<select name="content_spacing">'.$option('standard',(string)$settings['content_spacing'],'Standard').$option('relaxed',(string)$settings['content_spacing'],'Relaxed').'</select></label>'
            .'<label>Reading width<select name="reading_width">'.$option('standard',(string)$settings['reading_width'],'Standard').$option('narrow',(string)$settings['reading_width'],'Narrow reading column').'</select></label></section>'
            .'<section class="settings-card"><h2>Interaction and visibility</h2>'
            .'<label class="check-row"><input type="checkbox" name="high_contrast"'.$checked('high_contrast').'> Increase interface contrast</label>'
            .'<label class="check-row"><input type="checkbox" name="emphasize_links"'.$checked('emphasize_links').'> Underline links in content</label>'
            .'<label class="check-row"><input type="checkbox" name="enhanced_focus"'.$checked('enhanced_focus').'> Make keyboard focus more prominent</label>'
            .'<label class="check-row"><input type="checkbox" name="reduced_motion"'.$checked('reduced_motion').'> Reduce nonessential motion</label></section>'
            .'<button class="button" type="submit">Save accessibility preferences</button></form>'
            .'<aside class="settings-card accessibility-preview" aria-labelledby="preview-title"><p class="eyebrow">Live page preview</p><h2 id="preview-title">A calmer reading sample</h2><p>Koravik should remain understandable without relying on color, motion, or tiny controls.</p><p><a href="#preview-title">This sample link shows your link preference</a>.</p><button class="button secondary" type="button">Sample action</button></aside></div>'
            .'<form method="post" action="/settings/accessibility/reset"><input type="hidden" name="csrf" value="'.$csrf.'"><button class="quiet-button" type="submit">Restore accessibility defaults</button></form>';
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Accessibility · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><a class="skip-link" href="#main">Skip to content</a><main id="main" class="page" tabindex="-1">'.$body.'</main><footer>Koravik helps you act, then get back to living.</footer></body></html>';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
