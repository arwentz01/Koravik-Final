<?php

declare(strict_types=1);

namespace Koravik\Platform\Journey;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;

final class HealingHomeController
{
    public function __construct(private readonly Database $database)
    {
    }

    public function handle(string $method, string $path): bool
    {
        if ($method !== 'GET' || !in_array($path, ['/home', '/healing-home'], true)) {
            return false;
        }

        $account = Security::requireAccount();
        $journey = (new JourneyService($this->database))->homeForAccount((string) $account['id']);
        echo $this->render($account, $journey);
        return true;
    }

    private function render(array $account, array $journey): string
    {
        $focus = $journey['focus_quest'];
        $focusHtml = $focus
            ? '<article class="home-focus"><p class="eyebrow">What matters now</p><h2>' . self::e((string) $focus['title']) . '</h2><p>' . self::e((string) ($focus['purpose'] ?: 'A commitment you chose to carry.')) . '</p><div class="next-step"><span>Next meaningful step</span><strong>' . self::e((string) ($focus['next_step'] ?: 'Open the Quest and decide what comes next.')) . '</strong></div><a class="button" href="/quests/' . self::e((string) $focus['id']) . '">Continue Quest</a></article>'
            : '<article class="home-focus empty-state"><p class="eyebrow">Quest Board</p><h2>Nothing is demanding your attention.</h2><p>You can begin something meaningful when you are ready.</p><a class="button" href="/quests/create">Begin a Quest</a></article>';

        $memory = $journey['chronicle'];
        $memoryHtml = $memory
            ? '<article class="home-place"><p class="eyebrow">Journal Table</p><h2>' . self::e((string) $memory['title']) . '</h2><p>' . self::e((string) $memory['body']) . '</p><a href="/chronicle">Open Chronicle</a></article>'
            : '<article class="home-place"><p class="eyebrow">Journal Table</p><h2>Your story has room to gather.</h2><p>Reflections and meaningful moments will rest here.</p><a href="/chronicle">Open Chronicle</a></article>';

        $reaction = $journey['reaction'];
        $fireplaceHtml = $reaction
            ? '<article class="home-place fireplace"><p class="eyebrow">By the fire</p><h2>' . self::e((string) $reaction['title']) . '</h2><p>' . self::e((string) $reaction['message']) . '</p><a href="/worlds/epic-ordinary/progress">See what changed</a></article>'
            : '<article class="home-place fireplace"><p class="eyebrow">By the fire</p><h2>The room is quiet, but not empty.</h2><p>The World will reflect what you deliberately allow it to notice.</p><a href="/worlds/epic-ordinary/play">Enter Epic Ordinary</a></article>';

        $rooms = '';
        foreach ($journey['rooms'] as $room) {
            $locked = (string) $room['state'] !== 'open';
            $rooms .= '<li class="home-room ' . ($locked ? 'locked' : 'open') . '"><span>' . self::e((string) $room['name']) . '</span><small>' . ($locked ? 'Waiting to be discovered' : 'Open') . '</small></li>';
        }

        $atmosphere = str_replace('_', ' ', (string) ($journey['state']['atmosphere'] ?? 'quiet morning'));
        $body = '<section class="healing-home-hero"><div><p class="eyebrow">Healing Home · ' . self::e(ucwords($atmosphere)) . '</p><h1>Welcome home, ' . self::e((string) $account['display_name']) . '.</h1><p>You do not have to carry everything at once. One honest next step is enough.</p></div><a class="button secondary" href="/worlds/epic-ordinary/play">Continue the story</a></section>' .
            '<section class="healing-home-grid">' . $focusHtml . $fireplaceHtml . $memoryHtml . '<article class="home-place companion-seat"><p class="eyebrow">Companion Chair</p><h2>A place for thoughtful help.</h2><p>The Companion may help you clarify, reflect, or draft—but never choose for you.</p><a href="/companion">Visit Companion</a></article></section>' .
            '<section class="home-rooms"><div class="section-heading"><div><p class="eyebrow">The house</p><h2>Familiar places and unopened doors</h2></div></div><ul>' . $rooms . '</ul></section>';

        return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Healing Home · Koravik</title><link rel="stylesheet" href="/assets/app.css"><link rel="stylesheet" href="/assets/journey.css"></head><body><main id="main" class="page">' . $body . '</main><footer>Koravik · Reality first. Story in service of life.</footer></body></html>';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
