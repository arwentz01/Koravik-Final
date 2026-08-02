<?php

declare(strict_types=1);

namespace Koravik\Platform\SourceReview;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Resilience\ResilienceService;
use Koravik\Platform\Security\Security;

final class SourceReviewController
{
    public function __construct(private readonly Database $database) {}

    public function handle(string $method,string $path): bool
    {
        if(!str_starts_with($path,'/source-review'))return false;
        $account=Security::account();if(!$account)return false;$accountId=(string)$account['id'];$service=new SourceReviewService($this->database);
        if($method==='GET'&&$path==='/source-review'){$this->index($service->summary($accountId,isset($_GET['owner'])?(string)$_GET['owner']:null));return true;}
        if($method==='GET'&&$path==='/source-review/later'){$this->later();return true;}
        if($method==='POST'&&$path==='/source-review/drafts'){$this->saveDraft($accountId);return true;}
        if($method==='GET'&&preg_match('#^/source-review/drafts/([a-f0-9-]{36})/resume$#',$path,$m)){$this->resumeDraft($accountId,$m[1]);return true;}
        if($method==='GET'&&preg_match('#^/source-review/drafts/room-note/([a-z0-9_]+)$#',$path,$m)){$this->roomNote($service->roomNoteDraft($accountId,$m[1]));return true;}
        if($method==='GET'&&preg_match('#^/source-review/drafts/gather-followup/([a-f0-9-]{36})$#',$path,$m)){$this->gatherFollowup($service->gatherFollowupDraft($accountId,$m[1]));return true;}
        return false;
    }

    private function index(array $summary): void
    {
        $items=$summary['items'];
        $counts=$summary['counts'];
        $buckets=$summary['buckets'];
        $filter=(string)$summary['filter'];
        $filters='';
        foreach(['all'=>'All','chronicle'=>'Chronicle','companion'=>'Companion','gather'=>'Gather','healing_home'=>'Healing Home'] as $key=>$label){
            $href=$key==='all'?'/source-review':'/source-review?owner='.$key;
            $filters.='<a class="button secondary" href="'.$href.'"'.($filter===$key?' aria-current="page"':'').'>'.$label.'</a>';
        }
        $top=$summary['top_item']?'<section class="surface source-inbox-top-priority"><p class="eyebrow">Top priority</p><h2>'.self::e((string)$summary['top_item']['title']).'</h2><p>'.self::e((string)$summary['top_item']['summary']).'</p><p class="local-actions"><a class="button" href="'.self::e((string)$summary['top_item']['href']).'">Review now</a><a class="button secondary" href="/source-review/later?resume='.self::e((string)$summary['top_item']['resume_token']).'">Resume later</a></p></section>':'';
        $cards='';
        foreach($items as $item){
            $cards.='<article class="source-inbox-card source-owner-'.self::e((string)$item['owner_key']).'"><p class="eyebrow">'.self::e((string)$item['source_owner']).' · '.self::e((string)$item['item_type']).'</p><h2>'.self::e((string)$item['title']).'</h2><p>'.self::e((string)$item['summary']).'</p><dl><div><dt>What changes</dt><dd>Only the source-owned review state changes after you choose an action.</dd></div><div><dt>What does not</dt><dd>No Quest, Chronicle entry, Gather event, Companion memory, or World fact is created from this inbox alone.</dd></div><div><dt>Who owns the result</dt><dd>'.self::e((string)$item['consequence']).'</dd></div></dl><p class="meta">Resume token '.self::e((string)$item['resume_token']).' · '.self::e((string)$item['updated_at']).' UTC</p><p class="local-actions"><a class="button secondary" href="'.self::e((string)$item['href']).'">Review source</a><a href="/source-review/later?resume='.self::e((string)$item['resume_token']).'">Resume later</a></p></article>';
        }
        $hub='<section class="surface source-review-decision-hub-polish"><h2>Source Review Decision Hub Polish</h2><p>This is the central “what wants my decision?” place. Companion proposals, Gather outcomes, Chronicle drafts, Healing Home promotions, Quest handoffs, and Moment suggestions stay grouped by owner until you explicitly choose a destination.</p><p><a href="/moments">Moment source console</a> · <a href="/chronicle/proposals">Chronicle proposals</a> · <a href="/companion">Companion proposals</a></p></section>';
        $body='<section class="page-heading hearth-source-inbox source-draft-review-center source-inbox-maturity"><div><p class="eyebrow">Source Inbox Maturity</p><h1>Decisions waiting by source owner.</h1><p>The Source Draft Review Center gathers reviewable items from Chronicle, Companion, Gather, Healing Home, and notifications. It explains consequences before anything becomes a Quest, Chronicle entry, or public action.</p></div><a class="button" href="/hearth">Return to Hearth</a></section>'.$hub.'<section class="source-inbox-summary-strip"><article><strong>'.(int)$counts['total'].'</strong><span>Total waiting</span></article><article><strong>'.(int)$buckets['needs_decision'].'</strong><span>Needs decision</span></article><article><strong>'.(int)$buckets['draft_paths'].'</strong><span>Draft paths</span></article><article><strong>'.(int)$buckets['notices'].'</strong><span>Source notices</span></article></section><section class="surface source-inbox-filter-bar"><h2>Filter by source owner</h2><p class="local-actions">'.$filters.'</p></section>'.$top.'<section class="source-inbox-grid">'.($cards?:'<article class="empty-state source-inbox-empty-state"><h2>No cross-module decisions are waiting for this filter.</h2><p>Nothing was created, dismissed, published, or marked read. Try another source owner or return to Hearth.</p><p><a class="button secondary" href="/source-review">Clear filter</a></p></article>').'</section>';
        $this->render('Source review',$body);
    }

    private function later(): void
    {
        $token=substr(preg_replace('/[^a-z0-9]/i','',(string)($_GET['resume']??''))?:'pending',0,12);
        $this->render('Resume later','<section class="page-heading source-inbox-resume-later"><div><p class="eyebrow">Resume Later</p><h1>This decision is still waiting.</h1><p>Nothing was dismissed, marked read, approved, or executed. Use this page as a safe parking place, then return when you are ready.</p></div></section><section class="surface"><h2>Resume token</h2><p class="meta">'.self::e($token).'</p><p class="local-actions"><a class="button" href="/source-review">Return to Source Inbox</a><a class="button secondary" href="/hearth">Return to Hearth</a></p></section>');
    }

    private function saveDraft(string $accountId): void
    {
        $this->csrf();
        $kind=preg_replace('/[^a-z0-9_.-]/i','',(string)($_POST['draft_kind']??'source_review.pending'))?:'source_review.pending';
        $target=(string)($_POST['target_url']??'/source-review');
        (new ResilienceService($this->database))->saveDraft($accountId,$kind,[
            'source_review_durable_draft'=>'Build 208 durable cross-module draft',
            'draft_kind'=>$kind,
            'source_owner'=>(string)($_POST['source_owner']??'Source Review'),
            'source_reference'=>(string)($_POST['source_reference']??''),
            'destination_type'=>(string)($_POST['destination_type']??'review'),
            'target_url'=>$target,
            'title'=>(string)($_POST['title']??'Source Review draft'),
            'body'=>(string)($_POST['body']??''),
            'provenance_timeline'=>(string)($_POST['provenance_timeline']??'source captured → durable draft saved → destination still requires explicit review'),
        ]);
        $_SESSION['flash']='Durable Source Review draft saved. It is recoverable from the Recovery Center for 30 days.';
        header('Location: /source-review',true,303);
    }

    private function resumeDraft(string $accountId,string $id): void
    {
        $draft=(new ResilienceService($this->database))->draft($accountId,$id);
        if(!$draft){http_response_code(404);$this->render('Draft unavailable','<section class="empty-state"><h1>Draft unavailable.</h1><p>This durable draft is missing, expired, or not yours.</p></section>');return;}
        $payload=(array)$draft['payload'];
        $target=(string)($payload['target_url']??'/source-review');
        $body='<section class="page-heading durable-cross-module-drafts draft-provenance-timeline"><div><p class="eyebrow">Durable Cross-Module Draft</p><h1>'.self::e((string)($payload['title']??'Saved draft')).'</h1><p>This draft was saved without creating a destination record. Resume only when you are ready to continue review.</p></div><a href="/recovery-center">Recovery Center</a></section><section class="surface"><h2>Draft provenance timeline</h2><ol><li>Source owner: '.self::e((string)($payload['source_owner']??'Source Review')).'</li><li>Source reference: '.self::e((string)($payload['source_reference']??'')).'</li><li>'.self::e((string)($payload['provenance_timeline']??'destination still requires explicit review')).'</li></ol><p>'.self::e((string)($payload['body']??'')).'</p><p class="meta">Expires '.self::e((string)$draft['expires_at']).' UTC.</p><p class="local-actions"><a class="button" href="'.self::e($target).'">Resume destination review</a><a class="button secondary" href="/source-review">Return to Source Inbox</a></p></section>';
        $this->render('Resume Source Review draft',$body);
    }

    private function roomNote(?array $row): void
    {
        if(!$row){http_response_code(404);$this->render('Room note unavailable','<section class="empty-state"><h1>Room note unavailable.</h1></section>');return;}
        $title='From '.ucwords(str_replace('_',' ',(string)$row['room_key']));
        $note=(string)$row['note_text'];
        $quest='/quests/create?source=healing_home.room_note&source_reference='.rawurlencode((string)$row['room_key']).'&title='.rawurlencode($title).'&description='.rawurlencode($note);
        $chronicle='/chronicle/new?context=healing_home_journal_table&title='.rawurlencode($title).'&body='.rawurlencode($note).'&tags=healing-home,room-note';
        $body='<section class="page-heading healing-home-room-note-promotion decision-consequence-preview"><div><p class="eyebrow">Healing Home Room Note Promotion · Decision Consequence Preview</p><h1>Choose what this private note becomes, if anything.</h1><p>Promotion never happens automatically. Review the consequence before starting a draft.</p></div><a href="/home/rooms/'.self::e((string)$row['room_key']).'">Return to room</a></section><section class="surface"><h2>'.self::e((string)$row['name']).'</h2><p>'.self::e($note).'</p><dl><div><dt>What changes</dt><dd>A draft form opens with this note as starting context.</dd></div><div><dt>What does not</dt><dd>The room note remains in Healing Home and is not deleted, published, or sent to Worlds.</dd></div><div><dt>Who owns the result</dt><dd>Quests owns saved actions. Chronicle owns saved reflections.</dd></div></dl><p class="local-actions"><a class="button" href="'.self::e($quest).'">Start Quest draft</a><a class="button secondary" href="'.self::e($chronicle).'">Start Chronicle draft</a></p><form method="post" action="/source-review/drafts"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><input type="hidden" name="draft_kind" value="source_review.room_note"><input type="hidden" name="source_owner" value="Healing Home"><input type="hidden" name="source_reference" value="'.self::e((string)$row['room_key']).'"><input type="hidden" name="destination_type" value="quest_or_chronicle"><input type="hidden" name="target_url" value="'.self::e($chronicle).'"><input type="hidden" name="title" value="'.self::e($title).'"><input type="hidden" name="body" value="'.self::e($note).'"><button class="button secondary">Save durable draft</button></form></section>';
        $this->render('Room note promotion',$body);
    }

    private function gatherFollowup(?array $row): void
    {
        if(!$row){http_response_code(404);$this->render('Follow-up unavailable','<section class="empty-state"><h1>Follow-up unavailable.</h1></section>');return;}
        $quest='/quests/create?source=gather.followup&source_reference='.rawurlencode((string)$row['id']).'&title='.rawurlencode((string)$row['title']).'&description='.rawurlencode((string)$row['message']);
        $chronicle='/chronicle/new?context=gather_followup&title='.rawurlencode((string)$row['title']).'&body='.rawurlencode((string)$row['message']).'&tags=gather,follow-up';
        $body='<section class="page-heading gather-followup-draft-bridge decision-consequence-preview"><div><p class="eyebrow">Gather Follow-Up to Quest/Chronicle Drafts</p><h1>Review before a follow-up becomes private work or memory.</h1><p>Gather keeps the event and follow-up truth. A destination draft starts only when you choose it.</p></div><a href="/source-review">Source Inbox</a></section><section class="surface"><h2>'.self::e((string)$row['title']).'</h2><p>'.self::e((string)$row['message']).'</p><dl><div><dt>What changes</dt><dd>A Quest or Chronicle draft opens with minimized Gather provenance.</dd></div><div><dt>What does not</dt><dd>The Gather follow-up remains a Gather draft; no guest communication is sent.</dd></div><div><dt>Who owns the result</dt><dd>Gather owns follow-up truth. Quests or Chronicle owns any saved destination record.</dd></div></dl><p class="local-actions"><a class="button" href="'.self::e($quest).'">Start Quest draft</a><a class="button secondary" href="'.self::e($chronicle).'">Start Chronicle draft</a></p><form method="post" action="/source-review/drafts"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><input type="hidden" name="draft_kind" value="source_review.gather_followup"><input type="hidden" name="source_owner" value="Gather"><input type="hidden" name="source_reference" value="'.self::e((string)$row['id']).'"><input type="hidden" name="destination_type" value="quest_or_chronicle"><input type="hidden" name="target_url" value="'.self::e($chronicle).'"><input type="hidden" name="title" value="'.self::e((string)$row['title']).'"><input type="hidden" name="body" value="'.self::e((string)$row['message']).'"><button class="button secondary">Save durable draft</button></form></section>';
        $this->render('Gather follow-up draft',$body);
    }

    private function render(string $title,string $body): void
    {
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"></head><body><main id="main" class="page">'.$body.'</main></body></html>';
    }

    private function csrf(): void
    {
        if(!Security::verifyCsrf((string)($_POST['csrf']??'')))throw new \RuntimeException('Your session changed. Please try again.');
    }

    private static function e(string $v): string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
