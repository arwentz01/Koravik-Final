<?php

declare(strict_types=1);

namespace Koravik\Platform\Search;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;

final class SearchController
{
    public function __construct(private readonly Database $database) {}

    public function handle(string $method, string $path): bool
    {
        if ($method !== 'GET' || $path !== '/search') return false;
        $account = Security::account();
        if (!$account) {
            header('Location: /login', true, 303);
            return true;
        }
        $query = isset($_GET['q']) ? (string)$_GET['q'] : '';
        $results = (new SearchService($this->database))->search((string)$account['id'], $query);
        $this->render($results);
        return true;
    }

    private function render(array $results): void
    {
        $query = (string)$results['query'];
        $body = '<section class="page-heading"><div><p class="eyebrow">Global search</p><h1>Find what you are looking for.</h1><p>Results stay grouped by the part of Koravik that owns them.</p></div></section>' .
            '<section class="panel"><form method="get" action="/search" role="search"><label for="global-search">Search Koravik<input id="global-search" name="q" type="search" maxlength="120" value="'.self::e($query).'" placeholder="Quest, event, Beacon page, date, or World" autofocus></label><button class="button" type="submit">Search</button></form></section>';

        if ($query === '') {
            $body .= '<section class="panel search-owner-guide"><h2>Search without giving away ownership.</h2><p>Try a Quest title, a phrase from your Chronicle, a Gather event, a Beacon page, a Health date, or the name of a World. Koravik checks authorization before showing each result and labels the owner beside it.</p></section>';
        } elseif ((int)$results['total'] === 0) {
            $body .= '<section class="empty-state"><h2>No results for “'.self::e($query).'.”</h2><p>Try a shorter phrase or a different word. Nothing was changed.</p></section>';
        } else {
            $body .= '<p class="meta">'.(int)$results['total'].' result'.((int)$results['total']===1?'':'s').' for “'.self::e($query).'.”</p>';
            $body .= $this->questResults($results['quests']);
            $body .= $this->chronicleResults($results['chronicle']);
            $body .= $this->worldResults($results['worlds']);
            $body .= $this->gatherResults($results['gather']);
            $body .= $this->beaconResults($results['beacon']);
            $body .= $this->healthResults($results['health']);
            $body .= $this->homeNoteResults($results['home_notes']);
        }

        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Search · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><a class="skip-link" href="#main">Skip to content</a><header class="app-header"><a class="brand" href="/hearth">Koravik</a><nav aria-label="Primary"><a href="/hearth">Hearth</a><a href="/quests">Quests</a><a href="/chronicle">Chronicle</a><a href="/worlds">Worlds</a><a href="/search" aria-current="page">Search</a><a href="/notifications">Notifications</a></nav></header><main id="main" class="page" tabindex="-1">'.$body.'</main><footer>Koravik helps you act, then get back to living.</footer></body></html>';
    }

    private function questResults(array $rows): string
    {
        if (!$rows) return '';
        $cards='';
        foreach($rows as $row) {
            $cards.='<article class="card"><div><p class="eyebrow">Quests · '.self::e(ucwords(str_replace('_',' ',(string)$row['quest_type']))).'</p><h2>'.self::e((string)$row['title']).'</h2>'.($row['snippet']!==''?'<p>'.self::e((string)$row['snippet']).'</p>':'').'<p class="meta">Status: '.self::e((string)$row['lifecycle_status']).'</p></div><a class="button" href="/quests/'.self::e((string)$row['id']).'">Open Quest</a></article>';
        }
        return '<section><div class="section-heading"><h2>Quests</h2><span>'.count($rows).'</span></div><div class="grid">'.$cards.'</div></section>';
    }

    private function chronicleResults(array $rows): string
    {
        if (!$rows) return '';
        $cards='';
        foreach($rows as $row) {
            $cards.='<article class="card"><div><p class="eyebrow">Chronicle · '.self::e(ucfirst((string)$row['entry_type'])).'</p><h2>'.self::e((string)$row['title']).'</h2>'.($row['snippet']!==''?'<p>'.self::e((string)$row['snippet']).'</p>':'').'<p class="meta">Preserved '.self::e((string)$row['created_at']).' UTC</p></div><a class="button secondary" href="/chronicle">Open Chronicle</a></article>';
        }
        return '<section><div class="section-heading"><h2>Chronicle</h2><span>'.count($rows).'</span></div><div class="grid">'.$cards.'</div></section>';
    }

    private function worldResults(array $rows): string
    {
        if (!$rows) return '';
        $cards='';
        foreach($rows as $row) {
            $cards.='<article class="card"><div><p class="eyebrow">Worlds · '.self::e(ucfirst((string)$row['installation_status'])).'</p><h2>'.self::e((string)$row['name']).'</h2><p>'.self::e((string)$row['tagline']).'</p>'.($row['snippet']!==''?'<p class="meta">'.self::e((string)$row['snippet']).'</p>':'').'</div><a class="button" href="/worlds/'.self::e((string)$row['world_key']).'">Review World</a></article>';
        }
        return '<section><div class="section-heading"><h2>Worlds</h2><span>'.count($rows).'</span></div><div class="grid">'.$cards.'</div></section>';
    }

    private function gatherResults(array $rows): string
    {
        if (!$rows) return '';
        $cards='';
        foreach($rows as $row) {
            $cards.='<article class="card"><div><p class="eyebrow">Gather · Event truth</p><h2>'.self::e((string)$row['title']).'</h2>'.($row['snippet']!==''?'<p>'.self::e((string)$row['snippet']).'</p>':'').'<p class="meta">'.self::e((string)$row['starts_at']).' UTC · '.self::e((string)($row['lifecycle_status']??'scheduled')).'</p></div><a class="button" href="/gather/events/'.self::e((string)$row['id']).'">Open Event</a></article>';
        }
        return '<section><div class="section-heading"><h2>Gather</h2><span>'.count($rows).'</span></div><div class="grid">'.$cards.'</div></section>';
    }

    private function beaconResults(array $rows): string
    {
        if (!$rows) return '';
        $cards='';
        foreach($rows as $row) {
            $cards.='<article class="card"><div><p class="eyebrow">Beacon · '.self::e(ucfirst((string)$row['visibility'])).'</p><h2>'.self::e((string)$row['title']).'</h2>'.($row['snippet']!==''?'<p>'.self::e((string)$row['snippet']).'</p>':'').'<p class="meta">'.self::e(ucwords(str_replace("_"," ",(string)$row['page_type']))).'</p></div><a class="button" href="/beacon/pages/'.self::e((string)$row['id']).'/edit">Open Beacon page</a></article>';
        }
        return '<section><div class="section-heading"><h2>Beacon</h2><span>'.count($rows).'</span></div><div class="grid">'.$cards.'</div></section>';
    }

    private function healthResults(array $rows): string
    {
        if (!$rows) return '';
        $cards='';
        foreach($rows as $row) {
            $share=(bool)$row['share_derived_fact']?'Derived band may be shared by consent':'Private record';
            $cards.='<article class="card"><div><p class="eyebrow">Health · Private observation</p><h2>'.self::e((string)$row['observed_on']).'</h2><p>Energy '.(int)$row['energy_level'].' of 5</p><p class="meta">'.self::e($share).'. Notes and feeling words are not exposed in search snippets.</p></div><a class="button secondary" href="/health/checkins/'.self::e((string)$row['id']).'">Open Health record</a></article>';
        }
        return '<section><div class="section-heading"><h2>Health</h2><span>'.count($rows).'</span></div><div class="grid">'.$cards.'</div></section>';
    }

    private function homeNoteResults(array $rows): string
    {
        if (!$rows) return '';
        $cards='';
        foreach($rows as $row) {
            $cards.='<article class="card healing-home-room-note-search"><div><p class="eyebrow">Healing Home · Private room note</p><h2>'.self::e((string)$row['name']).'</h2>'.($row['snippet']!==''?'<p>'.self::e((string)$row['snippet']).'</p>':'').'<p class="meta">Room notes stay in Healing Home unless you explicitly copy them elsewhere.</p></div><a class="button secondary" href="/home/rooms/'.self::e((string)$row['room_key']).'">Open room</a></article>';
        }
        return '<section><div class="section-heading"><h2>Healing Home room notes</h2><span>'.count($rows).'</span></div><div class="grid">'.$cards.'</div></section>';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
    }
}
