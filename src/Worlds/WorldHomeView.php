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
            . $this->storyGateway($dashboard['active_world'])
            . $this->activeWorld($dashboard['active_world'])
            . $this->reactions($dashboard['reactions'])
            . $this->worldTrust()
            . $this->catalog($dashboard['catalog']);
    }

    private function storyGateway(?array $world): string
    {
        if ($world === null) {
            return '';
        }

        $chapter = self::label((string) ($world['current_chapter'] ?? 'Story ready'));
        $scene = self::label((string) ($world['current_scene'] ?? 'Continue'));
        $objective = trim((string) ($world['objective_title'] ?? ''));
        $permission = (int) ($world['quest_fact_granted'] ?? 0) === 1
            ? 'Quest-completion facts may create future explainable reactions.'
            : 'Quest-completion permission is revoked; the story continues without future Quest facts.';

        return '<section class="world-story-gateway" aria-labelledby="world-story-gateway-title">'
            . '<div><p class="eyebrow">Story doorway</p><h2 id="world-story-gateway-title">Epic Ordinary is waiting at the threshold.</h2>'
            . '<p>The active story is in <strong>' . self::e($chapter) . '</strong>, scene <strong>' . self::e($scene) . '</strong>. '
            . 'It is fictional World State, not a real-life score.</p></div>'
            . '<dl class="world-state-ledger"><div><dt>Current objective</dt><dd>' . self::e($objective !== '' ? $objective : 'No active World objective') . '</dd></div>'
            . '<div><dt>Latest keepsake</dt><dd>' . self::e((string) (($world['keepsake_name'] ?? '') ?: 'No fictional keepsake yet')) . '</dd></div>'
            . '<div><dt>Fact boundary</dt><dd>' . self::e($permission) . '</dd></div></dl></section>';
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
        $objective = trim((string) ($world['objective_title'] ?? ''));
        $latestReaction = trim((string) ($world['latest_reaction_title'] ?? ''));
        $latestFact = trim((string) ($world['latest_reaction_fact'] ?? ''));

        return '<section class="active-world-card" aria-labelledby="active-world-title"><div class="active-world-copy">'
            . '<p class="eyebrow">Active World - ' . self::e((string) $world['name']) . '</p>'
            . '<h2 id="active-world-title">' . $chapter . '</h2><p class="active-world-scene">' . $scene . '</p>'
            . '<p>' . self::e((string) $world['tagline']) . '</p>'
            . '<p class="world-review-status">' . self::e($reactionStatus) . '</p>'
            . '<div class="world-continuation-cues"><p><strong>Story objective</strong><span>' . self::e($objective !== '' ? $objective : 'The World is ready for a new authored beat.') . '</span></p>'
            . '<p><strong>Latest signal</strong><span>' . self::e($latestReaction !== '' ? $latestReaction : 'No recent interpreted change') . '</span></p>'
            . '<p><strong>Received fact</strong><span>' . self::e($latestFact !== '' ? $latestFact : 'No private notes, Chronicle prose, or Companion memory received') . '</span></p></div>'
            . '<div class="local-actions"><a class="button" href="/worlds/' . $worldKey . '/play">Continue story</a>'
            . '<a class="button secondary" href="/worlds/' . $worldKey . '/progress">View World State</a>'
            . '<a href="/worlds/' . $worldKey . '">Permissions and details</a></div></div>'
            . '<aside class="active-world-context" aria-label="Current World context"><span>Relationship</span>'
            . '<strong>' . self::e(self::label((string) ($world['relationship_stage'] ?? 'New'))) . '</strong>'
            . '<small>Trust ' . (int) ($world['trust_score'] ?? 0) . ' - fictional World State</small></aside></section>';
    }

    private function reactions(array $reactions): string
    {
        $cards = '';
        foreach ($reactions as $reaction) {
            $unread = $reaction['reviewed_at'] === null;
            $id = self::e((string) $reaction['id']);
            $cards .= '<article class="world-reaction-card' . ($unread ? ' is-new' : '') . '">'
                . '<div><p class="eyebrow">' . self::e((string) $reaction['world_name']) . ' - '
                . ($unread ? '<span class="new-reaction-label">New</span>' : 'Reviewed') . '</p>'
                . '<h3>' . self::e((string) $reaction['title']) . '</h3>'
                . '<p>' . self::e((string) $reaction['message']) . '</p>'
                . '<dl class="world-reaction-mini"><div><dt>Approved fact</dt><dd>' . self::e((string) (($reaction['source_fact_summary'] ?? '') ?: 'A minimized approved fact')) . '</dd></div>'
                . '<div><dt>World rule</dt><dd>' . self::e((string) (($reaction['rule_key'] ?? '') ?: 'Epic Ordinary authored reaction')) . '</dd></div></dl>'
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

    private function worldTrust(): string
    {
        return '<section class="world-trust-strip" aria-labelledby="world-trust-title"><h2 id="world-trust-title">What Worlds own</h2>'
            . '<p>Worlds own fictional progress, choices, objectives, keepsakes, relationship state, and explainable reactions. They do not own Quest completion, Chronicle prose, Companion memory, account secrets, or unrelated records.</p>'
            . '<p class="local-actions"><a class="button secondary" href="/privacy">Review privacy</a><a class="button secondary" href="/home/rooms/fireplace">Open Fireplace reactions</a><a class="button secondary" href="/home/rooms/eastern_room">Open Eastern Room</a></p></section>';
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
