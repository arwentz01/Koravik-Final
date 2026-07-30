<?php

declare(strict_types=1);

namespace Koravik\Worlds;

use Koravik\Platform\Security\Security;

final class WorldHomeView
{
    public function render(array $dashboard): string
    {
        return '<section class="worlds-heading page-heading"><div><p class="eyebrow">Worlds</p>'
            . '<h1>Continue the story that knows where you left it.</h1>'
            . '<p>Worlds are optional, independent, and responsive only to the fact categories you permit.</p></div>'
            . '<a href="/worlds/installed">Manage installed Worlds</a></section>'
            . $this->activeWorld($dashboard['active_world'])
            . $this->reactions($dashboard['reactions'])
            . $this->catalog($dashboard['catalog']);
    }

    private function activeWorld(?array $world): string
    {
        if ($world === null) {
            return '<section class="worlds-empty state-panel" aria-labelledby="active-world-title">'
                . '<p class="eyebrow">Active World</p><h2 id="active-world-title">No story is active.</h2>'
                . '<p>Install a World when you want a fictional layer. Koravik remains fully usable without one.</p>'
                . '<a class="button" href="#world-catalog">Browse available Worlds</a></section>';
        }

        $worldKey = self::e((string) $world['world_key']);
        $chapter = self::label((string) ($world['current_chapter'] ?? 'Story ready'));
        $scene = self::label((string) ($world['current_scene'] ?? 'Continue'));
        $count = (int) $world['unread_reaction_count'];
        $reactionStatus = $count === 0
            ? 'No new interpreted changes.'
            : $count . ' new interpreted ' . ($count === 1 ? 'change' : 'changes') . ' to review.';

        return '<section class="active-world-card" aria-labelledby="active-world-title"><div class="active-world-copy">'
            . '<p class="eyebrow">Active World · ' . self::e((string) $world['name']) . '</p>'
            . '<h2 id="active-world-title">' . $chapter . '</h2><p class="active-world-scene">' . $scene . '</p>'
            . '<p>' . self::e((string) $world['tagline']) . '</p>'
            . '<p class="world-review-status">' . self::e($reactionStatus) . '</p>'
            . '<div class="local-actions"><a class="button" href="/worlds/' . $worldKey . '/play">Continue story</a>'
            . '<a class="button secondary" href="/worlds/' . $worldKey . '/progress">View World State</a>'
            . '<a href="/worlds/' . $worldKey . '">Permissions and details</a></div></div>'
            . '<aside class="active-world-context" aria-label="Current World context"><span>Relationship</span>'
            . '<strong>' . self::e(self::label((string) ($world['relationship_stage'] ?? 'New'))) . '</strong>'
            . '<small>Trust ' . (int) ($world['trust_score'] ?? 0) . ' · fictional World State</small></aside></section>';
    }

    private function reactions(array $reactions): string
    {
        $cards = '';
        foreach ($reactions as $reaction) {
            $unread = $reaction['reviewed_at'] === null;
            $id = self::e((string) $reaction['id']);
            $cards .= '<article class="world-reaction-card' . ($unread ? ' is-new' : '') . '">'
                . '<div><p class="eyebrow">' . self::e((string) $reaction['world_name']) . ' · '
                . ($unread ? '<span class="new-reaction-label">New</span>' : 'Reviewed') . '</p>'
                . '<h3>' . self::e((string) $reaction['title']) . '</h3>'
                . '<p>' . self::e((string) $reaction['message']) . '</p>'
                . '<a href="/worlds/' . self::e((string) $reaction['world_key']) . '/reactions/' . $id . '">Why did this change?</a></div>'
                . ($unread
                    ? '<form method="post" action="/worlds/reactions/' . $id . '/review">'
                        . '<input type="hidden" name="csrf" value="' . self::e(Security::csrfToken()) . '">'
                        . '<button class="quiet-button" type="submit">Mark reviewed</button></form>'
                    : '<p class="reviewed-note">Reviewed</p>')
                . '</article>';
        }

        return '<section class="world-reactions" aria-labelledby="world-reactions-title"><div class="section-heading">'
            . '<div><p class="eyebrow">Explainable changes</p><h2 id="world-reactions-title">What the Worlds noticed</h2></div></div>'
            . ($cards !== ''
                ? '<div class="world-reaction-list">' . $cards . '</div>'
                : '<div class="empty-state"><h3>No interpreted changes yet.</h3><p>Worlds will not invent activity when no approved fact has arrived.</p></div>')
            . '</section>';
    }

    private function catalog(array $worlds): string
    {
        $cards = '';
        foreach ($worlds as $world) {
            $status = $world['installation_status']
                ? self::label((string) $world['installation_status'])
                : 'Not installed';
            $cards .= '<article class="world-catalog-card"><p class="eyebrow">' . self::e($status) . '</p>'
                . '<h3>' . self::e((string) $world['name']) . '</h3><p>' . self::e((string) $world['tagline']) . '</p>'
                . '<p class="meta">Package ' . self::e((string) $world['package_version']) . '</p>'
                . '<a href="/worlds/' . self::e((string) $world['world_key']) . '">Review World</a></article>';
        }

        return '<section id="world-catalog" aria-labelledby="world-catalog-title"><div class="section-heading">'
            . '<div><p class="eyebrow">Catalog</p><h2 id="world-catalog-title">Available Worlds</h2></div></div>'
            . ($cards !== ''
                ? '<div class="world-catalog-grid">' . $cards . '</div>'
                : '<div class="empty-state"><h3>No Worlds are available.</h3></div>')
            . '</section>';
    }

    private static function label(string $value): string
    {
        return ucwords(str_replace('-', ' ', $value));
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
