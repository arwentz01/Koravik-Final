<?php

declare(strict_types=1);

namespace Koravik\Platform\UI;

use Koravik\Platform\Settings\SettingsService;
use Throwable;

final class VisualSystem
{
    public function apply(string $html, ?array $account, string $path): string
    {
        if (!str_contains($html, '<html') || !str_contains($html, '<body')) {
            return $html;
        }

        $classes = ['koravik-ui', 'route-' . $this->routeClass($path)];
        if ($account) {
            try {
                $settings = (new SettingsService(\database()))->get((string) $account['id']);
                $classes[] = 'appearance-' . preg_replace('/[^a-z-]/', '', (string) $settings['appearance']);
                if ((int) $settings['reduced_motion'] === 1) $classes[] = 'reduce-motion';
                if ((int) $settings['high_contrast'] === 1) $classes[] = 'high-contrast';
                $classes[] = 'text-' . preg_replace('/[^a-z-]/', '', (string) $settings['text_scale']);
                $classes[] = 'typeface-' . preg_replace('/[^a-z-]/', '', (string) $settings['typeface']);
                $classes[] = 'spacing-' . preg_replace('/[^a-z-]/', '', (string) $settings['content_spacing']);
                $classes[] = 'width-' . preg_replace('/[^a-z-]/', '', (string) $settings['reading_width']);
                if ((int) $settings['emphasize_links'] === 1) $classes[] = 'emphasize-links';
                if ((int) $settings['enhanced_focus'] === 1) $classes[] = 'enhanced-focus';
            } catch (Throwable) {
                $classes[] = 'appearance-system';
            }
        }

        if (!str_contains($html, '/assets/visual-system.css')) {
            $html = str_replace('</head>', '<link rel="stylesheet" href="/assets/visual-system.css"></head>', $html);
        }
        if (!str_contains($html, '/assets/accessibility.css')) {
            $html = str_replace('</head>', '<link rel="stylesheet" href="/assets/accessibility.css"></head>', $html);
        }
        $html = preg_replace('/<body([^>]*)class="([^"]*)"/', '<body$1class="$2 ' . implode(' ', $classes) . '"', $html, 1, $count) ?? $html;
        if (($count ?? 0) === 0) {
            $html = preg_replace('/<body([^>]*)>/', '<body$1 class="' . implode(' ', $classes) . '">', $html, 1) ?? $html;
        }

        $html = str_replace('class="page-heading"', 'class="page-heading page-header"', $html);
        $html = str_replace('class="form-panel"', 'class="form-panel surface surface-editor"', $html);
        $html = str_replace('class="panel"', 'class="panel surface"', $html);
        $html = str_replace('class="empty-state"', 'class="empty-state state-panel state-empty"', $html);
        $html = str_replace('class="notice error"', 'class="notice error state-panel state-error"', $html);
        $html = str_replace('class="notice"', 'class="notice state-panel state-success"', $html);
        $html = str_replace('class="meta"', 'class="meta provenance"', $html);

        if (str_contains($html, '<main') && !str_contains($html, 'aria-label="Current location"')) {
            $label = $this->sectionLabel($path);
            $crumb = '<nav class="context-nav" aria-label="Current location"><a href="' . $this->sectionHref($path) . '">' . self::e($label) . '</a><span aria-hidden="true">/</span><span>' . self::e($this->pageLabel($path)) . '</span></nav>';
            $html = preg_replace('/(<main[^>]*>)/', '$1' . $crumb, $html, 1) ?? $html;
        }
        return $html;
    }

    private function routeClass(string $path): string
    {
        $value = trim($path, '/');
        return $value === '' ? 'home' : trim((string) preg_replace('/[^a-z0-9]+/i', '-', $value), '-');
    }

    private function sectionHref(string $path): string
    {
        foreach (['/quests','/chronicle','/worlds','/companion','/settings','/privacy','/audit','/search','/notifications'] as $root) {
            if ($path === $root || str_starts_with($path, $root . '/')) return $root;
        }
        return '/hearth';
    }

    private function sectionLabel(string $path): string
    {
        return match ($this->sectionHref($path)) {
            '/quests' => 'Quests', '/chronicle' => 'Chronicle', '/worlds' => 'Worlds', '/companion' => 'Companion',
            '/settings' => 'Settings', '/privacy' => 'Privacy', '/audit' => 'Audit activity', '/search' => 'Search',
            '/notifications' => 'Notifications', default => 'Hearth'
        };
    }

    private function pageLabel(string $path): string
    {
        if ($path === $this->sectionHref($path)) return 'Overview';
        $tail = basename($path);
        if (preg_match('/^[a-f0-9-]{36}$/', $tail)) return 'Detail';
        return ucwords(str_replace(['-', '_'], ' ', $tail));
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
