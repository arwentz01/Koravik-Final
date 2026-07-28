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
            ['title'=>'Your day-to-day places','description'=>'The places where real-life work and reflection live.','items'=>[['Hearth','What matters now, return summaries, and bounded next steps.','/hearth'],['Quests','Actions, habits, projects, journeys, and responsibilities.','/quests'],['Chronicle','Personal reflections, preserved moments, and approved drafts.','/chronicle']]],
            ['title'=>'Optional story and assistance','description'=>'Helpful layers that never take ownership of your real-life records.','items'=>[['Worlds','Install, continue, suspend, and review fictional Worlds.','/worlds'],['Companion','Ask for help, review proposals, manage context, and control memory.','/companion']]],
            ['title'=>'Find and review','description'=>'Utilities for locating information and seeing what changed.','items'=>[['Search','Find authorized Quests, Chronicle entries, and Worlds.','/search'],['Notifications','Review explainable notices and attention preferences.','/notifications'],['Audit activity','See consequential actions and their reasons.','/audit']]],
            ['title'=>'Account, privacy, and control','description'=>'The visual home for how Koravik works for you and what it may use.','items'=>[['Settings','Appearance, accessibility, time, and account preferences.','/settings'],['Privacy','Permissions, consent, sources, recipients, and revocation effects.','/privacy'],['Security','Password changes and session protection.','/settings/security'],['Data controls','Export your data or request staged account closure.','/settings/data']]],
        ];
        $sections='';foreach($groups as $group){$cards='';foreach($group['items'] as $item)$cards.='<article class="home-card"><h3><a href="'.$item[2].'">'.self::e($item[0]).'</a></h3><p>'.self::e($item[1]).'</p></article>';$sections.='<section class="guide-section"><div class="section-heading"><div><h2>'.self::e($group['title']).'</h2><p>'.self::e($group['description']).'</p></div></div><div class="home-grid">'.$cards.'</div></section>';}
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Koravik guide · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><main id="main" class="page"><section class="page-heading"><div><p class="eyebrow">Koravik guide</p><h1>Everything has a home.</h1><p>This map explains where each capability belongs and what it owns.</p></div></section>'.$sections.'</main></body></html>';
        return true;
    }
    private static function e(string $v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
