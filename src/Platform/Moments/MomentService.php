<?php

declare(strict_types=1);

namespace Koravik\Platform\Moments;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Experience\ChronicleManagementService;
use PDO;
use RuntimeException;

final class MomentService
{
    public function __construct(private readonly Database $database) {}

    // Queue rule: prefer one arrival Moment over several shallow interruptions.

    public function submit(string $accountId, array $moment): string
    {
        $sourceModule = trim((string)($moment['source_module'] ?? 'Healing Home'));
        $sourceType = trim((string)($moment['source_type'] ?? 'ambient'));
        $sourceId = $this->validUuid((string)($moment['source_id'] ?? '')) ? (string)$moment['source_id'] : null;
        $momentKey = trim((string)($moment['moment_key'] ?? ''));
        $title = trim((string)($moment['title'] ?? ''));
        $body = trim((string)($moment['body'] ?? ''));
        if ($sourceModule === '' || $sourceType === '' || $momentKey === '' || $title === '' || $body === '') {
            throw new RuntimeException('Moment candidates need source, key, title, and body.');
        }
        $visibility = in_array((string)($moment['visibility'] ?? 'ambient'), ['ambient','arrival','chronicle'], true) ? (string)$moment['visibility'] : 'ambient';
        $priority = in_array((string)($moment['priority'] ?? 'low'), ['quiet','low','medium','high'], true) ? (string)$moment['priority'] : 'low';
        $template = in_array((string)($moment['scene_template'] ?? 'room'), ['caretaker','room','silent','memory','companion'], true) ? (string)$moment['scene_template'] : 'room';
        $id = self::uuid();
        $this->database->pdo()->prepare('INSERT INTO platform_moments (id,account_id,source_module,source_type,source_id,moment_key,scene_template,speaker_label,primary_object,ambient_detail,recommended_action_label,title,body,room_key,visibility,priority,status,provenance_summary,excluded_summary,created_at) VALUES (:id,:account_id,:source_module,:source_type,:source_id,:moment_key,:scene_template,:speaker_label,:primary_object,:ambient_detail,:recommended_action_label,:title,:body,:room_key,:visibility,:priority,"queued",:provenance,:excluded,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE scene_template=VALUES(scene_template), speaker_label=VALUES(speaker_label), primary_object=VALUES(primary_object), ambient_detail=VALUES(ambient_detail), recommended_action_label=VALUES(recommended_action_label), title=VALUES(title), body=VALUES(body), room_key=VALUES(room_key), visibility=VALUES(visibility), priority=VALUES(priority), provenance_summary=VALUES(provenance_summary), excluded_summary=VALUES(excluded_summary)')->execute([
            'id'=>$id,
            'account_id'=>$accountId,
            'source_module'=>$sourceModule,
            'source_type'=>$sourceType,
            'source_id'=>$sourceId,
            'moment_key'=>$momentKey,
            'scene_template'=>$template,
            'speaker_label'=>trim((string)($moment['speaker_label'] ?? '')) ?: null,
            'primary_object'=>trim((string)($moment['primary_object'] ?? '')) ?: null,
            'ambient_detail'=>trim((string)($moment['ambient_detail'] ?? '')) ?: null,
            'recommended_action_label'=>trim((string)($moment['recommended_action_label'] ?? 'Continue gently')) ?: 'Continue gently',
            'title'=>$title,
            'body'=>$body,
            'room_key'=>trim((string)($moment['room_key'] ?? '')) ?: null,
            'visibility'=>$visibility,
            'priority'=>$priority,
            'provenance'=>trim((string)($moment['provenance_summary'] ?? 'A source-owned change submitted a bounded Moment candidate.')),
            'excluded'=>trim((string)($moment['excluded_summary'] ?? 'Private notes, Companion memory, Health records, account secrets, and unrelated records stayed private.')),
        ]);

        $lookup = $this->database->pdo()->prepare('SELECT id FROM platform_moments WHERE account_id=:account_id AND source_module=:source_module AND source_type=:source_type AND ((source_id IS NULL AND :source_id_lookup IS NULL) OR source_id=:source_id_match) AND moment_key=:moment_key LIMIT 1');
        $lookup->execute(['account_id'=>$accountId,'source_module'=>$sourceModule,'source_type'=>$sourceType,'source_id_lookup'=>$sourceId,'source_id_match'=>$sourceId,'moment_key'=>$momentKey]);
        return (string)($lookup->fetchColumn() ?: $id);
    }

    public function seedForAccount(string $accountId): void
    {
        $this->database->transaction(function (PDO $pdo) use ($accountId): void {
            $this->seedQuestMoments($pdo, $accountId);
            $this->seedGatherMoments($pdo, $accountId);
            $this->seedHealthMoments($pdo, $accountId);
            $this->seedSourceReviewMoments($pdo, $accountId);
            $this->seedCompanionMoments($pdo, $accountId);
            $this->seedWorldProgressMoments($pdo, $accountId);
            $reactions = $pdo->prepare('SELECT wr.id, wr.title, wr.message, wr.explanation, wr.source_fact_summary, wr.created_at
                FROM world_reactions wr JOIN world_installations wi ON wi.id=wr.installation_id
                WHERE wi.account_id=:account_id AND wi.world_key="epic-ordinary"
                ORDER BY wr.created_at DESC LIMIT 20');
            $reactions->execute(['account_id'=>$accountId]);
            foreach ($reactions->fetchAll() as $reaction) {
                $copy = $this->copyPack('room', (string)$reaction['title'], 'fireplace');
                $this->submit($accountId, [
                    'source_module'=>'Worlds',
                    'source_type'=>'world_reaction',
                    'source_id'=>(string)$reaction['id'],
                    'moment_key'=>'world-reaction-arrival',
                    'title'=>(string)$reaction['title'],
                    'body'=>(string)$reaction['message'],
                    'room_key'=>'fireplace',
                    'scene_template'=>'room',
                    'primary_object'=>'fireplace',
                    'ambient_detail'=>$copy['ambient'],
                    'recommended_action_label'=>'Step past the hearth',
                    'visibility'=>'arrival',
                    'priority'=>'medium',
                    'provenance_summary'=>(string)($reaction['source_fact_summary'] ?: 'A minimized approved World fact was interpreted.'),
                    'excluded_summary'=>'Quest notes, Chronicle prose, Companion memory, Health records, account secrets, and unrelated private data stayed private.',
                ]);
            }
            $changes = $pdo->prepare('SELECT id,title,description,room_key,source_type,created_at FROM healing_home_changes WHERE account_id=:account_id AND source_type IN ("epic_reclamation","garden_tending","relationship_conversation","world_choice") ORDER BY created_at DESC LIMIT 20');
            $changes->execute(['account_id'=>$accountId]);
            foreach ($changes->fetchAll() as $change) {
                $visibility = (string)$change['source_type'] === 'epic_reclamation' ? 'ambient' : 'chronicle';
                $template = $this->templateForChange((string)$change['source_type'], (string)$change['room_key']);
                $copy = $this->copyPack($template, (string)$change['title'], (string)$change['room_key']);
                $this->submit($accountId, [
                    'source_module'=>'Healing Home',
                    'source_type'=>(string)$change['source_type'],
                    'source_id'=>(string)$change['id'],
                    'moment_key'=>'healing-home-visible-change',
                    'title'=>(string)$change['title'],
                    'body'=>(string)$change['description'],
                    'room_key'=>(string)$change['room_key'],
                    'scene_template'=>$template,
                    'speaker_label'=>(string)$change['source_type'] === 'relationship_conversation' ? 'The Caretaker' : null,
                    'primary_object'=>$this->objectForRoom((string)$change['room_key']),
                    'ambient_detail'=>$copy['ambient'],
                    'recommended_action_label'=>$copy['action'],
                    'visibility'=>$visibility,
                    'priority'=>$visibility === 'ambient' ? 'quiet' : 'low',
                    'provenance_summary'=>'Healing Home submitted a source-labeled visible room change as a Moment candidate.',
                    'excluded_summary'=>'Private room notes, Chronicle prose, Companion memory, Health records, and unrelated account data stayed private.',
                ]);
            }
            $conversations = $pdo->prepare('SELECT id, character_response, player_choice, created_at FROM relationship_conversations WHERE account_id=:account_id AND character_key="caretaker" ORDER BY created_at DESC LIMIT 20');
            $conversations->execute(['account_id'=>$accountId]);
            foreach ($conversations->fetchAll() as $conversation) {
                $copy = $this->copyPack('caretaker', 'Caretaker conversation', 'entry_hall');
                $this->submit($accountId, [
                    'source_module'=>'Journey',
                    'source_type'=>'relationship_conversation',
                    'source_id'=>(string)$conversation['id'],
                    'moment_key'=>'caretaker-presence-scene',
                    'scene_template'=>'caretaker',
                    'speaker_label'=>'The Caretaker',
                    'primary_object'=>'brass lantern',
                    'ambient_detail'=>$copy['ambient'],
                    'recommended_action_label'=>$copy['action'],
                    'title'=>'The Caretaker left the lantern lit',
                    'body'=>(string)$conversation['character_response'],
                    'room_key'=>'entry_hall',
                    'visibility'=>'chronicle',
                    'priority'=>'low',
                    'provenance_summary'=>'A bounded Caretaker conversation became eligible for a remembered Moment.',
                    'excluded_summary'=>'Private room notes, Chronicle prose, Companion memory, Health records, and unrelated account data stayed private.',
                ]);
            }
            $keepsakes = $pdo->prepare('SELECT id,name,meaning,room_key,source_type,created_at FROM healing_home_keepsakes WHERE account_id=:account_id AND displayed=1 ORDER BY created_at DESC LIMIT 20');
            $keepsakes->execute(['account_id'=>$accountId]);
            foreach ($keepsakes->fetchAll() as $keepsake) {
                $name = (string)$keepsake['name'];
                $isCompanionTrace = str_contains(mb_strtolower($name), 'robin') || str_contains(mb_strtolower($name), 'feather');
                $copy = $this->copyPack($isCompanionTrace ? 'companion' : 'memory', $name, (string)$keepsake['room_key']);
                $this->submit($accountId, [
                    'source_module'=>'Healing Home',
                    'source_type'=>'keepsake',
                    'source_id'=>(string)$keepsake['id'],
                    'moment_key'=>'memory-object-scene',
                    'scene_template'=>$isCompanionTrace ? 'companion' : 'memory',
                    'primary_object'=>$name,
                    'ambient_detail'=>$copy['ambient'],
                    'recommended_action_label'=>$copy['action'],
                    'title'=>$name,
                    'body'=>(string)$keepsake['meaning'],
                    'room_key'=>(string)$keepsake['room_key'],
                    'visibility'=>'chronicle',
                    'priority'=>'low',
                    'provenance_summary'=>$isCompanionTrace ? 'A displayed Healing Home keepsake became eligible for a companion-ready visitor trace Moment.' : 'A displayed Healing Home keepsake became eligible for a remembered object Moment.',
                    'excluded_summary'=>'Keepsakes do not expose private notes, Chronicle prose, Companion memory, Health records, or unrelated account data.',
                ]);
            }
        });
    }

    private function seedQuestMoments(PDO $pdo, string $accountId): void
    {
        $completed = $pdo->prepare('SELECT qo.id occurrence_id, qo.completed_at, q.id quest_id, q.title, q.quest_type, q.purpose FROM quest_occurrences qo JOIN quests q ON q.id=qo.quest_id WHERE qo.account_id=:account_id AND qo.status="completed" ORDER BY qo.completed_at DESC LIMIT 20');
        $completed->execute(['account_id'=>$accountId]);
        foreach ($completed->fetchAll() as $row) {
            $copy = $this->copyPack('memory', (string)$row['title'], 'library');
            $this->submit($accountId, [
                'source_module'=>'Quests',
                'source_type'=>'quest_completion',
                'source_id'=>(string)$row['occurrence_id'],
                'moment_key'=>'quest-completion-memory',
                'scene_template'=>'memory',
                'primary_object'=>'completed Quest card',
                'ambient_detail'=>$copy['ambient'],
                'recommended_action_label'=>'Remember the action',
                'title'=>'A Quest came to rest',
                'body'=>'“'.(string)$row['title'].'” was completed. The action is evidence of movement, not a score.',
                'room_key'=>'library',
                'visibility'=>'chronicle',
                'priority'=>'low',
                'provenance_summary'=>'Quests submitted a completed occurrence as a remembered Moment candidate.',
                'excluded_summary'=>'Quest private notes, recurrence internals, account secrets, Health records, and unrelated Chronicle prose stayed private.',
            ]);
        }
        $resolutions = $pdo->prepare('SELECT qr.id, qr.outcome, qr.reflection, qr.resolved_at, q.title FROM quest_resolutions qr JOIN quests q ON q.id=qr.quest_id WHERE qr.account_id=:account_id ORDER BY qr.resolved_at DESC LIMIT 20');
        $resolutions->execute(['account_id'=>$accountId]);
        foreach ($resolutions->fetchAll() as $row) {
            $copy = $this->copyPack('silent', (string)$row['title'], 'workshop');
            $this->submit($accountId, [
                'source_module'=>'Quests',
                'source_type'=>'quest_resolution',
                'source_id'=>(string)$row['id'],
                'moment_key'=>'quest-resolution-silent',
                'scene_template'=>'silent',
                'primary_object'=>'folded plan',
                'ambient_detail'=>$copy['ambient'],
                'recommended_action_label'=>'Let the decision settle',
                'title'=>'A Quest changed shape',
                'body'=>'“'.(string)$row['title'].'” was marked '.(string)$row['outcome'].'.',
                'room_key'=>'workshop',
                'visibility'=>'chronicle',
                'priority'=>'quiet',
                'provenance_summary'=>'Quests submitted a resolved commitment as a bounded Moment candidate.',
                'excluded_summary'=>'Resolution reflections remain in Quests/Chronicle review paths and are not copied into the Moment beyond minimized status.',
            ]);
        }
    }

    private function seedGatherMoments(PDO $pdo, string $accountId): void
    {
        $events = $pdo->prepare('SELECT id,title,venue,lifecycle_status,closeout_note,closed_at FROM gather_events WHERE account_id=:account_id AND lifecycle_status IN ("completed","cancelled","archived") AND closed_at IS NOT NULL ORDER BY closed_at DESC LIMIT 20');
        $events->execute(['account_id'=>$accountId]);
        foreach ($events->fetchAll() as $row) {
            $copy = $this->copyPack('room', (string)$row['title'], 'hearth_table');
            $this->submit($accountId, [
                'source_module'=>'Gather',
                'source_type'=>'event_closeout',
                'source_id'=>(string)$row['id'],
                'moment_key'=>'gather-closeout-room',
                'scene_template'=>'room',
                'primary_object'=>'set table',
                'ambient_detail'=>$copy['ambient'],
                'recommended_action_label'=>'Review the gathering',
                'title'=>'A gathering closed',
                'body'=>(string)$row['title'].' is now '.(string)$row['lifecycle_status'].'.',
                'room_key'=>'hearth_table',
                'visibility'=>'chronicle',
                'priority'=>'low',
                'provenance_summary'=>'Gather submitted event closeout state as a source-owned Moment candidate.',
                'excluded_summary'=>'Guest lists, attendee emails, private notes, and unsent follow-up drafts stayed in Gather.',
            ]);
        }
        $outcomes = $pdo->prepare('SELECT id,outcome_type,summary,status,created_at FROM gather_outcome_proposals WHERE account_id=:account_id AND status IN ("proposed","approved","applied") ORDER BY created_at DESC LIMIT 20');
        $outcomes->execute(['account_id'=>$accountId]);
        foreach ($outcomes->fetchAll() as $row) {
            $copy = $this->copyPack('memory', (string)$row['summary'], 'hearth_table');
            $this->submit($accountId, [
                'source_module'=>'Gather',
                'source_type'=>'outcome_proposal',
                'source_id'=>(string)$row['id'],
                'moment_key'=>'gather-outcome-memory',
                'scene_template'=>'memory',
                'primary_object'=>'follow-up card',
                'ambient_detail'=>$copy['ambient'],
                'recommended_action_label'=>'Prepare review',
                'title'=>'A Gather outcome is ready to consider',
                'body'=>(string)$row['summary'],
                'room_key'=>'hearth_table',
                'visibility'=>'chronicle',
                'priority'=>'low',
                'provenance_summary'=>'Gather submitted a minimized outcome proposal; destination ownership remains explicit.',
                'excluded_summary'=>'RSVP details, guest contacts, attendance internals, and unsent communications stayed private to Gather.',
            ]);
        }
    }

    private function seedHealthMoments(PDO $pdo, string $accountId): void
    {
        $health = $pdo->prepare('SELECT id,observed_on,energy_level,feeling_word FROM health_wellbeing_checkins WHERE account_id=:account_id AND share_derived_fact=1 ORDER BY observed_on DESC LIMIT 10');
        $health->execute(['account_id'=>$accountId]);
        foreach ($health->fetchAll() as $row) {
            $copy = $this->copyPack('silent', 'Health check-in', 'garden');
            $this->submit($accountId, [
                'source_module'=>'Health',
                'source_type'=>'consented_derived_trend',
                'source_id'=>(string)$row['id'],
                'moment_key'=>'health-consented-silent',
                'scene_template'=>'silent',
                'primary_object'=>'garden glass',
                'ambient_detail'=>$copy['ambient'],
                'recommended_action_label'=>'Notice gently',
                'title'=>'A consented Health signal changed the weather',
                'body'=>'A shared derived Health signal was recorded for '.(string)$row['observed_on'].'.',
                'room_key'=>'garden',
                'visibility'=>'ambient',
                'priority'=>'quiet',
                'provenance_summary'=>'Health submitted only a consented derived signal as a quiet Moment candidate.',
                'excluded_summary'=>'Private Health note text, raw trend analysis, clinical data, and unrelated account records stayed private.',
            ]);
        }
    }

    private function seedSourceReviewMoments(PDO $pdo, string $accountId): void
    {
        $drafts = $pdo->prepare('SELECT id,form_key,payload_json,updated_at FROM platform_form_drafts WHERE account_id=:account_id AND form_key LIKE "source_review.%" AND expires_at>UTC_TIMESTAMP() ORDER BY updated_at DESC LIMIT 20');
        $drafts->execute(['account_id'=>$accountId]);
        foreach ($drafts->fetchAll() as $row) {
            $payload = json_decode((string)$row['payload_json'], true) ?: [];
            $title = trim((string)($payload['title'] ?? 'Source Review draft'));
            $copy = $this->copyPack('silent', $title, 'entry_hall');
            $this->submit($accountId, [
                'source_module'=>'Source Review',
                'source_type'=>'durable_draft',
                'source_id'=>(string)$row['id'],
                'moment_key'=>'source-review-draft-silent',
                'scene_template'=>'silent',
                'primary_object'=>'sealed draft',
                'ambient_detail'=>$copy['ambient'],
                'recommended_action_label'=>'Open source review',
                'title'=>'A source decision is waiting',
                'body'=>$title,
                'room_key'=>'entry_hall',
                'visibility'=>'chronicle',
                'priority'=>'quiet',
                'provenance_summary'=>'Source Review submitted a durable draft pointer as a Moment candidate.',
                'excluded_summary'=>'Draft payload details, private room notes, and destination form content stayed in Source Review.',
            ]);
        }
        $reviews = $pdo->prepare('SELECT id,source_module,title,status,created_at FROM chronicle_reflection_reviews WHERE account_id=:account_id AND source_module!="Moment Engine" AND status="proposed" ORDER BY created_at DESC LIMIT 20');
        $reviews->execute(['account_id'=>$accountId]);
        foreach ($reviews->fetchAll() as $row) {
            $copy = $this->copyPack('memory', (string)$row['title'], 'library');
            $this->submit($accountId, [
                'source_module'=>'Chronicle',
                'source_type'=>'reflection_review',
                'source_id'=>(string)$row['id'],
                'moment_key'=>'chronicle-review-memory',
                'scene_template'=>'memory',
                'primary_object'=>'open review page',
                'ambient_detail'=>$copy['ambient'],
                'recommended_action_label'=>'Review before saving',
                'title'=>'A reflection is ready for review',
                'body'=>(string)$row['title'],
                'room_key'=>'library',
                'visibility'=>'chronicle',
                'priority'=>'low',
                'provenance_summary'=>'Chronicle submitted a proposed reflection review as a Moment candidate.',
                'excluded_summary'=>'Draft body text is not copied into the Moment library; Chronicle remains the owner of saved prose.',
            ]);
        }
    }

    private function seedCompanionMoments(PDO $pdo, string $accountId): void
    {
        $proposals = $pdo->prepare('SELECT id,proposal_type,status,title,owning_module,reasoning,updated_at FROM companion_proposals WHERE account_id=:account_id AND status IN ("awaiting_approval","approved") ORDER BY updated_at DESC LIMIT 20');
        $proposals->execute(['account_id'=>$accountId]);
        foreach ($proposals->fetchAll() as $row) {
            $copy = $this->copyPack('companion', (string)$row['title'], 'entry_hall');
            $this->submit($accountId, [
                'source_module'=>'Companion',
                'source_type'=>'proposal_trace',
                'source_id'=>(string)$row['id'],
                'moment_key'=>'companion-proposal-trace',
                'scene_template'=>'companion',
                'speaker_label'=>'Companion',
                'primary_object'=>'visitor note',
                'ambient_detail'=>$copy['ambient'],
                'recommended_action_label'=>'Review the proposal',
                'title'=>'Companion left a proposal trace',
                'body'=>(string)$row['title'],
                'room_key'=>'entry_hall',
                'visibility'=>'chronicle',
                'priority'=>'quiet',
                'provenance_summary'=>'Companion submitted approval-state metadata only, not private memory.',
                'excluded_summary'=>'Request text, private Companion memory, proposed payload JSON, and execution details stayed in Companion.',
            ]);
        }
    }

    private function seedWorldProgressMoments(PDO $pdo, string $accountId): void
    {
        $progress = $pdo->prepare('SELECT wi.id installation_id, wi.world_key, wnp.current_arc, wnp.current_chapter, wnp.current_scene, wnp.updated_at FROM world_installations wi JOIN world_narrative_progress wnp ON wnp.installation_id=wi.id WHERE wi.account_id=:account_id ORDER BY wnp.updated_at DESC LIMIT 20');
        $progress->execute(['account_id'=>$accountId]);
        foreach ($progress->fetchAll() as $row) {
            $copy = $this->copyPack('room', (string)$row['current_chapter'], 'library');
            $this->submit($accountId, [
                'source_module'=>'Worlds',
                'source_type'=>'chapter_progress',
                'source_id'=>(string)$row['installation_id'],
                'moment_key'=>'world-chapter-progress',
                'scene_template'=>'room',
                'primary_object'=>'chapter door',
                'ambient_detail'=>$copy['ambient'],
                'recommended_action_label'=>'Return to the World',
                'title'=>'A World chapter is open',
                'body'=>(string)$row['world_key'].' is at '.(string)$row['current_chapter'].' / '.(string)$row['current_scene'].'.',
                'room_key'=>'library',
                'visibility'=>'ambient',
                'priority'=>'quiet',
                'provenance_summary'=>'Worlds submitted minimized narrative progress as a room-state Moment candidate.',
                'excluded_summary'=>'World internals, private Chronicle text, unapproved facts, and unrelated Journey records stayed private.',
            ]);
        }
    }

    public function next(string $accountId): ?array
    {
        $this->seedForAccount($accountId);
        $statement = $this->database->pdo()->prepare('SELECT * FROM platform_moments WHERE account_id=:account_id AND status="queued" AND visibility="arrival" ORDER BY FIELD(priority,"high","medium","low","quiet"), created_at ASC LIMIT 1');
        $statement->execute(['account_id'=>$accountId]);
        $moment = $statement->fetch();
        return $moment ?: null;
    }

    public function remembered(string $accountId): array
    {
        $this->seedForAccount($accountId);
        $statement = $this->database->pdo()->prepare('SELECT * FROM platform_moments WHERE account_id=:account_id AND status IN ("presented","archived") ORDER BY COALESCE(archived_at,presented_at,created_at) DESC LIMIT 100');
        $statement->execute(['account_id'=>$accountId]);
        return $statement->fetchAll();
    }

    public function all(string $accountId): array
    {
        $this->seedForAccount($accountId);
        $statement = $this->database->pdo()->prepare('SELECT * FROM platform_moments WHERE account_id=:account_id ORDER BY FIELD(status,"queued","presented","archived","dismissed"), created_at DESC LIMIT 100');
        $statement->execute(['account_id'=>$accountId]);
        return $statement->fetchAll();
    }

    public function get(string $accountId, string $id): array
    {
        if (!$this->validUuid($id)) throw new RuntimeException('Moment unavailable.');
        $statement = $this->database->pdo()->prepare('SELECT * FROM platform_moments WHERE account_id=:account_id AND id=:id LIMIT 1');
        $statement->execute(['account_id'=>$accountId,'id'=>$id]);
        $moment = $statement->fetch();
        if (!$moment) throw new RuntimeException('Moment unavailable.');
        return $moment;
    }

    public function present(string $accountId, string $id): void
    {
        $this->database->pdo()->prepare('UPDATE platform_moments SET status="presented", presented_at=COALESCE(presented_at,UTC_TIMESTAMP()) WHERE account_id=:account_id AND id=:id AND status="queued"')->execute(['account_id'=>$accountId,'id'=>$id]);
    }

    public function archive(string $accountId, string $id): void
    {
        $this->database->pdo()->prepare('UPDATE platform_moments SET status="archived", archived_at=COALESCE(archived_at,UTC_TIMESTAMP()) WHERE account_id=:account_id AND id=:id AND status IN ("queued","presented")')->execute(['account_id'=>$accountId,'id'=>$id]);
    }

    public function dismiss(string $accountId, string $id): void
    {
        $this->database->pdo()->prepare('UPDATE platform_moments SET status="dismissed", dismissed_at=COALESCE(dismissed_at,UTC_TIMESTAMP()) WHERE account_id=:account_id AND id=:id AND status IN ("queued","presented")')->execute(['account_id'=>$accountId,'id'=>$id]);
    }

    public function proposeChronicle(string $accountId, string $id): string
    {
        $moment = $this->get($accountId, $id);
        if (!empty($moment['chronicle_proposal_id'])) return (string)$moment['chronicle_proposal_id'];
        $body = trim((string)$moment['body']) . "\n\nMoment review context:\n"
            . 'Scene template: ' . (string)($moment['scene_template'] ?? 'room') . "\n"
            . 'Room: ' . (string)($moment['room_key'] ?: 'not room-bound') . "\n"
            . 'Source: ' . (string)$moment['source_module'] . ' / ' . (string)$moment['source_type'] . "\n"
            . 'Provenance: ' . (string)$moment['provenance_summary'] . "\n"
            . 'Excluded: ' . (string)$moment['excluded_summary'];
        $proposal = (new ChronicleManagementService($this->database))->createReflectionProposal($accountId, 'Moment Engine', 'Moment: ' . (string)$moment['title'], $body, $id);
        $this->database->pdo()->prepare('UPDATE platform_moments SET chronicle_proposal_id=:proposal, status=IF(status="queued","presented",status), presented_at=COALESCE(presented_at,UTC_TIMESTAMP()) WHERE account_id=:account_id AND id=:id')->execute(['proposal'=>$proposal,'account_id'=>$accountId,'id'=>$id]);
        return $proposal;
    }

    private function validUuid(string $id): bool
    {
        return preg_match('/^[a-f0-9-]{36}$/', $id) === 1;
    }

    private function templateForChange(string $sourceType, string $roomKey): string
    {
        if ($sourceType === 'relationship_conversation') return 'caretaker';
        if ($sourceType === 'epic_reclamation') return 'memory';
        if (in_array($roomKey, ['entry_hall','garden','fireplace','library','workshop','eastern_room'], true)) return 'room';
        return 'silent';
    }

    private function objectForRoom(string $roomKey): ?string
    {
        return match ($roomKey) {
            'fireplace' => 'fireplace',
            'garden' => 'garden window',
            'workshop' => 'unfinished shelf',
            'library' => 'open book',
            'entry_hall' => 'brass lantern',
            'eastern_room' => 'threshold nameplate',
            default => null,
        };
    }

    private function ambientForRoom(string $roomKey): string
    {
        return match ($roomKey) {
            'fireplace' => 'A warmer color gathers at the hearth.',
            'garden' => 'Rain marks the glass without asking anything of you.',
            'workshop' => 'Lamplight holds an unfinished thing gently.',
            'library' => 'Dust moves through a band of quiet light.',
            'entry_hall' => 'The lantern is lit before anyone speaks.',
            'eastern_room' => 'The threshold feels named, not claimed.',
            default => 'The room changes quietly enough to notice.',
        };
    }

    private function copyPack(string $template, string $title, string $roomKey = ''): array
    {
        $roomObject = $this->objectForRoom($roomKey) ?: 'the room';
        return match ($template) {
            'caretaker' => ['ambient'=>'The Caretaker answers with presence, not pressure; the lantern keeps the pause warm.','action'=>'Sit with it'],
            'silent' => ['ambient'=>'No one announces the change. '.$roomObject.' simply leaves enough evidence to notice.','action'=>'Let it be quiet'],
            'memory' => ['ambient'=>'A remembered object holds the shape of “'.mb_strimwidth($title,0,60,'…').'” without turning it into a reward.','action'=>'Inspect the object'],
            'companion' => ['ambient'=>'A companion-ready visitor trace arrived without becoming a chore or a command.','action'=>'Notice the visitor trace'],
            default => ['ambient'=>$this->ambientForRoom($roomKey) . ' The room carries this as evidence, not interruption.','action'=>'Notice and continue'],
        };
    }

    private static function uuid(): string
    {
        $b=random_bytes(16);$b[6]=chr((ord($b[6])&15)|64);$b[8]=chr((ord($b[8])&63)|128);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($b),4));
    }
}
