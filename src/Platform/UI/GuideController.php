<?php

declare(strict_types=1);

namespace Koravik\Platform\UI;

use Koravik\Platform\Security\Security;

final class GuideController
{
    public function handle(string $method,string $path):bool
    {
        if($method!=='GET'||$path!=='/guide'||!Security::account())return false;
        $groups=[
            ['title'=>'Act','description'=>'Create, continue, or complete real-life commitments without turning life into a scoreboard.','items'=>[['Hearth','Today’s command strip, focus, and bounded next steps.','/hearth'],['Quests','Actions, habits, projects, journeys, responsibilities, and timelines.','/quests']]],
            ['title'=>'Reflect','description'=>'Keep meaning intentionally. Nothing becomes Chronicle memory until you save or approve it.','items'=>[['Chronicle','Personal reflections, search, filters, and proposed reflection review.','/chronicle'],['Healing Home','A place-based guide to meaning, boundaries, and source threads.','/home']]],
            ['title'=>'Share','description'=>'Public surfaces are previewed and source-owned before anything leaves private space.','items'=>[['Beacon','Short links, public pages, campaigns, and preview safety.','/beacon'],['Gather','Events, RSVPs, signups, participant preferences, and public preview.','/gather']]],
            ['title'=>'Coordinate','description'=>'Shared spaces stay optional and contextual. Personal records do not transfer into them.','items'=>[['Organizations','Group operations with contextual roles.','/organizations'],['Households','Private household coordination and responsibility proposals.','/households']]],
            ['title'=>'Privacy','description'=>'Understand how Koravik owns data, grants permissions, and explains automation.','items'=>[['Source ownership explainer','How Hearth, Districts, Worlds, Companion, Beacon, Gather, and account data fit together.','/guide#source-ownership'],['Privacy','Permissions, consent, sources, recipients, and revocation effects.','/privacy'],['Data controls','Export review and closure consequence preview.','/settings/data']]],
            ['title'=>'Troubleshooting','description'=>'Recover safely when a session expires, a draft is interrupted, or a route is unavailable.','items'=>[['Recovery center','Saved drafts, sessions, and safe recovery links.','/recovery-center'],['System health','Admin release readiness and worker/mail queue operations.','/system/health']]],
        ];
        $sections='';foreach($groups as $group){$cards='';foreach($group['items'] as $item)$cards.='<article class="home-card"><h3><a href="'.$item[2].'">'.self::e($item[0]).'</a></h3><p>'.self::e($item[1]).'</p></article>';$sections.='<section class="guide-section"><div class="section-heading"><div><h2>'.self::e($group['title']).'</h2><p>'.self::e($group['description']).'</p></div></div><div class="home-grid">'.$cards.'</div></section>';}
        $source='<section id="source-ownership" class="surface source-ownership-explainer"><p class="eyebrow">Source Ownership Explainer</p><h2>How Koravik owns data</h2><p>Hearth composes. Districts own their records. Worlds interpret only approved minimized facts. Companion proposes, but destination modules execute. Beacon owns public links and pages. Gather owns RSVP, signup, attendance, and follow-up truth. Account data export and closure preserve these ownership boundaries.</p></section>';
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Koravik guide · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><main id="main" class="page"><section class="page-heading guide-help-center-completion"><div><p class="eyebrow">Guide / Help Center Completion</p><h1>Everything has a home.</h1><p>This map explains where each capability belongs, what it owns, and how to recover when something feels unclear.</p></div></section>'.$sections.$source.'</main></body></html>';
        return true;
    }
    private static function e(string $v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
