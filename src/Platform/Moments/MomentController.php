<?php

declare(strict_types=1);

namespace Koravik\Platform\Moments;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;

final class MomentController
{
    public function __construct(private readonly Database $database) {}

    // Remembered Moment groups include Caretaker scenes, room scenes, silent scenes, memory scenes, and companion scenes.

    public function handle(string $method, string $path): bool
    {
        if (!str_starts_with($path, '/moments')) return false;
        $account = Security::account();
        if (!$account) return false;
        $accountId = (string)$account['id'];
        $service = new MomentService($this->database);
        if ($method === 'GET' && $path === '/moments/next') {
            $moment = $service->next($accountId);
            if (!$moment) {
                echo $this->document('No Moment waiting', '<section class="page-heading moment-engine-foundation"><div><p class="eyebrow">Moment Engine Foundation</p><h1>No arrival Moment is waiting.</h1><p>The house can be quiet. The Moment Engine prefers one meaningful scene over several shallow interruptions.</p></div><a class="button" href="/home">Return home</a></section>');
                return true;
            }
            echo $this->renderMoment($moment, true);
            return true;
        }
        if ($method === 'GET' && $path === '/moments') {
            echo $this->renderLibrary($service->all($accountId), 'Moment Engine');
            return true;
        }
        if ($method === 'GET' && $path === '/moments/remembered') {
            echo $this->renderLibrary($service->remembered($accountId), 'Moments Remembered');
            return true;
        }
        if ($method === 'GET' && preg_match('#^/moments/([a-f0-9-]{36})$#', $path, $m)) {
            echo $this->renderMoment($service->get($accountId, $m[1]), false);
            return true;
        }
        if ($method === 'POST' && preg_match('#^/moments/([a-f0-9-]{36})/(present|archive|dismiss|chronicle)$#', $path, $m)) {
            if (!Security::verifyCsrf(isset($_POST['csrf']) ? (string)$_POST['csrf'] : null)) {
                $_SESSION['flash'] = 'Your session changed. Please try again.';
                header('Location: /moments/'.$m[1], true, 303);
                return true;
            }
            try {
                if ($m[2] === 'present') $service->present($accountId, $m[1]);
                if ($m[2] === 'archive') $service->archive($accountId, $m[1]);
                if ($m[2] === 'dismiss') $service->dismiss($accountId, $m[1]);
                if ($m[2] === 'chronicle') {
                    $service->proposeChronicle($accountId, $m[1]);
                    $_SESSION['flash'] = 'Moment prepared for Chronicle review.';
                    header('Location: /chronicle/proposals', true, 303);
                    return true;
                }
            } catch (RuntimeException $exception) {
                $_SESSION['flash'] = $exception->getMessage();
            }
            header('Location: /moments/remembered', true, 303);
            return true;
        }
        return false;
    }

    private function renderMoment(array $moment, bool $arrival): string
    {
        $id = self::e((string)$moment['id']);
        $continue = self::e((string)($moment['recommended_action_label'] ?: 'Continue gently'));
        $actions = '<form method="post" action="/moments/'.$id.'/present"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><button class="button">'.$continue.'</button></form><form method="post" action="/moments/'.$id.'/archive"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><button class="button secondary">Remember quietly</button></form><form method="post" action="/moments/'.$id.'/chronicle"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><button class="button secondary">Prepare Chronicle review</button></form><form method="post" action="/moments/'.$id.'/dismiss"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><button class="quiet-button">Let this pass</button></form>';
        $body = $this->flash().'<section class="moment-stage moment-arrival-scene moment-template-'.self::e((string)($moment['scene_template'] ?? 'room')).' moment-engine-foundation living-moment-presentation-polish" aria-labelledby="moment-title"><div class="moment-stage-art" aria-hidden="true"><span></span><span></span><span></span></div><div class="moment-stage-copy"><p class="eyebrow">Moment Scene Template · '.self::e((string)($moment['scene_template'] ?? 'room')).' · '.self::e((string)$moment['visibility']).'</p>'.$this->sceneLead($moment).'<h1 id="moment-title">'.self::e((string)$moment['title']).'</h1><p>'.self::e((string)$moment['body']).'</p><p class="meta">Room: '.self::e((string)($moment['room_key'] ?: 'not room-bound')).' · Source: '.self::e((string)$moment['source_module']).'</p><div class="hero-actions">'.$actions.'</div></div></section><section class="moment-provenance-panel room-trust-panel"><h2>Why this appeared</h2><p class="meta">Provenance stays quieter than the scene, but it remains available before Chronicle review.</p><dl class="reaction-explain-list"><div><dt>Source owner</dt><dd>'.self::e((string)$moment['source_module']).' owns the original change. Moment Engine owns only presentation and read/archive state.</dd></div><div><dt>Provenance</dt><dd>'.self::e((string)$moment['provenance_summary']).'</dd></div><div><dt>Deliberately excluded</dt><dd>'.self::e((string)$moment['excluded_summary']).'</dd></div><div><dt>Queue rule</dt><dd>One arrival Moment at a time. No repeated popups, no shame, no streak pressure.</dd></div></dl></section>';
        return $this->document($arrival ? 'Arrival Moment' : (string)$moment['title'], $body);
    }

    private function renderLibrary(array $moments, string $title): string
    {
        $cards = '';
        $groups = [];
        foreach ($moments as $moment) {
            $groups[(string)($moment['scene_template'] ?? 'room')][] = $moment;
        }
        $sourceCounts = [];
        foreach ($moments as $moment) $sourceCounts[(string)$moment['source_module']] = ($sourceCounts[(string)$moment['source_module']] ?? 0) + 1;
        $sourceSummary = '';
        foreach ($sourceCounts as $source => $count) $sourceSummary .= '<span>'.self::e($source).' · '.(int)$count.'</span>';
        $nav = '<nav class="moment-scene-filter-nav moment-inbox-tuning-controls" aria-label="Moment scene filters"><a href="#moment-scenes-caretaker">Caretaker</a><a href="#moment-scenes-room">Room</a><a href="#moment-scenes-silent">Silent</a><a href="#moment-scenes-memory">Memory</a><a href="#moment-scenes-companion">Companion</a></nav>';
        $review = '<section class="surface moment-source-review-console"><h2>Moment Source Review Console</h2><p>Source modules own the original facts. Moment Engine owns presentation state only. Use this panel to see which districts are currently contributing without exposing private payloads.</p><p class="moment-source-counts">'.($sourceSummary ?: '<span>No source submissions yet</span>').'</p><dl><div><dt>Moment Inbox / Tuning Controls</dt><dd>Default posture is quiet: one arrival Moment, Chronicle review by explicit action, and source types grouped before they become noise.</dd></div><div><dt>Source filters</dt><dd>Quests, Gather, Worlds, Companion, Health, Source Review, Chronicle, and Healing Home can submit minimized candidates; private content remains in the owner module.</dd></div><div><dt>Library polish</dt><dd>Cards group by scene type, expose source/status, and provide direct Chronicle preparation.</dd></div></dl></section>';
        foreach ($groups as $template => $items) {
            $cards .= '<section id="moment-scenes-'.self::e($template).'" class="moment-library-group"><h2>'.self::e(ucfirst($template)).' scenes</h2><div class="chronicle-list">';
            foreach ($items as $moment) {
                $mid = self::e((string)$moment['id']);
                $cards .= '<article class="chronicle-entry moment-card moment-template-'.self::e($template).' remembered-moment-actions"><p class="eyebrow">'.self::e((string)$moment['source_module']).' · '.self::e((string)$moment['status']).' · '.self::e((string)$moment['visibility']).'</p><h3>'.self::e((string)$moment['title']).'</h3><p>'.self::e(mb_strimwidth((string)$moment['body'],0,260,'…')).'</p><p class="meta">Room: '.self::e((string)($moment['room_key'] ?: 'not room-bound')).' · Object: '.self::e((string)($moment['primary_object'] ?: 'none')).'</p><p class="local-actions"><a href="/moments/'.$mid.'">Open Moment</a><form method="post" action="/moments/'.$mid.'/chronicle"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><button class="quiet-button" type="submit">Prepare Chronicle review</button></form></p></article>';
            }
            $cards .= '</div></section>';
        }
        $body = $this->flash().'<section class="page-heading moment-engine-foundation remembered-moment-library-upgrade moment-library-polish"><div><p class="eyebrow">Moment Engine Foundation</p><h1>'.self::e($title).'</h1><p>Meaningful changes can become ambience, one arrival scene, or Chronicle review. This is not a notification center.</p></div><div><a class="button" href="/moments/next">Next arrival Moment</a> <a class="button secondary" href="/home">Healing Home</a></div></section>'.$review.$nav.'<div class="moment-library-shell">'.($cards ?: '<article class="empty-state"><h2>No Moments yet.</h2><p>The house can be quiet; silence can be a Moment too.</p></article>').'</div>';
        return $this->document($title, $body);
    }

    private function sceneLead(array $moment): string
    {
        $template = (string)($moment['scene_template'] ?? 'room');
        $speaker = self::e((string)($moment['speaker_label'] ?: ''));
        $object = self::e((string)($moment['primary_object'] ?: 'the room'));
        $ambient = self::e((string)($moment['ambient_detail'] ?: 'The change is visible without becoming a task.'));
        return match ($template) {
            'caretaker' => '<div class="moment-scene-lead"><strong>'.$speaker.'</strong><span>The brass lantern is already lit.</span><p>'.$ambient.'</p></div>',
            'silent' => '<div class="moment-scene-lead"><strong>Silent scene</strong><span>No one speaks. '.$object.' carries the change.</span><p>'.$ambient.'</p></div>',
            'memory' => '<div class="moment-scene-lead"><strong>Memory object</strong><span>'.$object.' rests where you can find it again.</span><p>'.$ambient.'</p></div>',
            'companion' => '<div class="moment-scene-lead"><strong>Companion presence</strong><span>A visitor changed the room by being here.</span><p>'.$ambient.'</p></div>',
            default => '<div class="moment-scene-lead"><strong>Room scene</strong><span>'.$object.' is the first thing you notice.</span><p>'.$ambient.'</p></div>',
        };
    }

    private function flash(): string
    {
        $flash = isset($_SESSION['flash']) ? (string)$_SESSION['flash'] : '';
        unset($_SESSION['flash']);
        return $flash ? '<div class="notice" role="status">'.self::e($flash).'</div>' : '';
    }

    private function document(string $title, string $body): string
    {
        return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"><link rel="stylesheet" href="/assets/chronicle-management.css"><link rel="stylesheet" href="/assets/journey.css"><link rel="stylesheet" href="/assets/moments.css"></head><body><main class="page">'.$body.'</main></body></html>';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
