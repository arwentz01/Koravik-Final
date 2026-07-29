<?php

declare(strict_types=1);

namespace Koravik\Platform\Journey;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;

final class HealingHomeController
{
    public function __construct(private readonly Database $database) {}

    public function handle(string $method, string $path): bool
    {
        if ($method !== 'GET') return false;
        if (in_array($path, ['/home','/healing-home'], true)) {
            $account=Security::requireAccount();
            echo $this->renderHome($account,(new JourneyService($this->database))->homeForAccount((string)$account['id']));
            return true;
        }
        if (preg_match('#^/home/relationships/([a-z0-9-]+)$#',$path,$matches)) {
            $account=Security::requireAccount();
            $relationship=(new JourneyService($this->database))->relationshipForAccount((string)$account['id'],$matches[1]);
            if(!$relationship){http_response_code(404);echo $this->document('Relationship unavailable','<section class="panel"><h1>This relationship is unavailable.</h1><a href="/home">Return home</a></section>');return true;}
            echo $this->renderRelationship($relationship);return true;
        }
        return false;
    }

    private function renderHome(array $account,array $journey): string
    {
        $focus=$journey['focus_quest'];
        $focusHtml=$focus?'<article class="home-focus"><p class="eyebrow">What matters now</p><h2>'.self::e((string)$focus['title']).'</h2><p>'.self::e((string)($focus['purpose']?:'A commitment you chose to carry.')).'</p><div class="next-step"><span>Next meaningful step</span><strong>'.self::e((string)($focus['next_step']?:'Open the Quest and decide what comes next.')).'</strong></div><a class="button" href="/quests/'.self::e((string)$focus['id']).'">Continue Quest</a></article>':'<article class="home-focus empty-state"><p class="eyebrow">Quest Board</p><h2>Nothing is demanding your attention.</h2><p>You can begin something meaningful when you are ready.</p><a class="button" href="/quests/create">Begin a Quest</a></article>';
        $memory=$journey['chronicle'];
        $memoryHtml=$memory?'<article class="home-place"><p class="eyebrow">Journal Table</p><h2>'.self::e((string)$memory['title']).'</h2><p>'.self::e((string)$memory['body']).'</p><a href="/chronicle">Open Chronicle</a></article>':'<article class="home-place"><p class="eyebrow">Journal Table</p><h2>Your story has room to gather.</h2><p>Reflections and meaningful moments will rest here.</p><a href="/chronicle">Open Chronicle</a></article>';
        $changes='';foreach($journey['changes'] as $change)$changes.='<li><strong>'.self::e((string)$change['title']).'</strong><span>'.self::e((string)$change['description']).'</span><small>'.self::e(ucwords(str_replace('_',' ',(string)$change['room_key']))).'</small></li>';
        $changeHtml='<article class="home-place fireplace"><p class="eyebrow">What changed</p><h2>The house noticed.</h2>'.($changes?'<ol class="home-change-list">'.$changes.'</ol>':'<p>The World will reflect only what you deliberately allow it to notice.</p>').'<a href="/worlds/epic-ordinary/progress">See World history</a></article>';
        $keepsakes='';foreach($journey['keepsakes'] as $keepsake)$keepsakes.='<li><strong>'.self::e((string)$keepsake['name']).'</strong><span>'.self::e((string)$keepsake['meaning']).'</span></li>';
        $keepsakeHtml='<article class="home-place"><p class="eyebrow">Keepsakes</p><h2>Small things worth keeping.</h2>'.($keepsakes?'<ul class="keepsake-list">'.$keepsakes.'</ul>':'<p>No keepsakes are displayed yet. They arrive through meaningful story and reflection, not grinding.</p>').'</article>';
        $relationships='';foreach($journey['relationships'] as $relationship)$relationships.='<li><a href="/home/relationships/'.self::e((string)$relationship['character_key']).'"><strong>'.self::e((string)$relationship['character_name']).'</strong><span>'.self::e(ucfirst((string)$relationship['relationship_state'])).' · remembers '.(int)$relationship['familiarity'].'%</span></a></li>';
        $relationshipHtml='<article class="home-place companion-seat"><p class="eyebrow">Guest and resident memory</p><h2>People remember what was shared.</h2>'.($relationships?'<ul class="relationship-list">'.$relationships.'</ul>':'<p>No one has gathered here yet.</p>').'</article>';
        $rooms='';foreach($journey['rooms'] as $room){$locked=(string)$room['state']!=='open';$rooms.='<li class="home-room '.($locked?'locked':'open').'"><span>'.self::e((string)$room['name']).'</span><small>'.($locked?'Waiting to be discovered':'Open').'</small></li>';}
        $atmosphere=str_replace('_',' ',(string)($journey['state']['atmosphere']??'quiet morning'));
        $body='<section class="healing-home-hero"><div><p class="eyebrow">Healing Home · '.self::e(ucwords($atmosphere)).'</p><h1>Welcome home, '.self::e((string)$account['display_name']).'.</h1><p>You do not have to carry everything at once. One honest next step is enough.</p></div><a class="button secondary" href="/worlds/epic-ordinary/play">Continue the story</a></section><section class="healing-home-grid">'.$focusHtml.$changeHtml.$memoryHtml.$keepsakeHtml.$relationshipHtml.'<article class="home-place"><p class="eyebrow">Companion Chair</p><h2>A place for thoughtful help.</h2><p>The Companion may help you clarify, reflect, or draft—but never choose for you.</p><a href="/companion">Visit Companion</a></article></section><section class="home-rooms"><div class="section-heading"><div><p class="eyebrow">The house</p><h2>Familiar places and unopened doors</h2></div></div><ul>'.$rooms.'</ul></section>';
        return $this->document('Healing Home',$body);
    }

    private function renderRelationship(array $relationship): string
    {
        $memories='';foreach($relationship['memories'] as $memory)$memories.='<li><p class="eyebrow">'.self::e(ucwords(str_replace('_',' ',(string)$memory['memory_kind']))).'</p><p>'.self::e((string)$memory['summary']).'</p><small>'.self::e((string)$memory['created_at']).' UTC</small></li>';
        $body='<section class="relationship-hero"><p class="eyebrow">Relationship · '.self::e(ucfirst((string)$relationship['relationship_state'])).'</p><h1>'.self::e((string)$relationship['character_name']).'</h1><p>This history records what shaped the relationship. It is not a score, affection meter, or judgment.</p><a class="button secondary" href="/home">Return home</a></section><section class="relationship-memory-panel"><h2>Shared history</h2>'.($memories?'<ol class="relationship-memory-list">'.$memories.'</ol>':'<p>No shared moments have been recorded yet.</p>').'</section>';
        return $this->document((string)$relationship['character_name'],$body);
    }

    private function document(string $title,string $body): string
    {
        return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"><link rel="stylesheet" href="/assets/journey.css"></head><body><main id="main" class="page">'.$body.'</main><footer>Koravik · Reality first. Story in service of life.</footer></body></html>';
    }

    private static function e(string $value): string{return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
