<?php

declare(strict_types=1);

namespace Koravik\Platform\Search;

use Koravik\Platform\Database\Database;

final class SearchService
{
    public function __construct(private readonly Database $database) {}

    public function search(string $accountId, string $query): array
    {
        $query = trim(preg_replace('/\s+/', ' ', $query) ?? '');
        if ($query === '') return ['query'=>'','quests'=>[],'chronicle'=>[],'worlds'=>[],'gather'=>[],'beacon'=>[],'health'=>[],'home_notes'=>[],'total'=>0];
        if (mb_strlen($query) > 120) $query = mb_substr($query, 0, 120);
        $like = '%' . $this->escapeLike($query) . '%';
        $pdo = $this->database->pdo();

        $quests = $pdo->prepare(
            'SELECT id,title,description,quest_type,lifecycle_status
             FROM quests
             WHERE account_id=:account_id AND (title LIKE :title ESCAPE "\\\\" OR description LIKE :description ESCAPE "\\\\")
             ORDER BY lifecycle_status="active" DESC, updated_at DESC LIMIT 20'
        );
        $quests->execute(['account_id'=>$accountId,'title'=>$like,'description'=>$like]);

        $chronicle = $pdo->prepare(
            'SELECT id,title,body,entry_type,created_at
             FROM chronicle_entries
             WHERE account_id=:account_id AND status="active" AND (title LIKE :title ESCAPE "\\\\" OR body LIKE :body ESCAPE "\\\\")
             ORDER BY created_at DESC LIMIT 20'
        );
        $chronicle->execute(['account_id'=>$accountId,'title'=>$like,'body'=>$like]);

        $worlds = $pdo->prepare(
            'SELECT c.world_key,c.name,c.tagline,c.description,c.status,COALESCE(i.status,"available") AS installation_status
             FROM world_catalog c
             LEFT JOIN world_installations i ON i.world_key=c.world_key AND i.account_id=:account_id
             WHERE c.status="available" AND (c.name LIKE :name ESCAPE "\\\\" OR c.tagline LIKE :tagline ESCAPE "\\\\" OR c.description LIKE :description ESCAPE "\\\\")
             ORDER BY c.name LIMIT 20'
        );
        $worlds->execute(['account_id'=>$accountId,'name'=>$like,'tagline'=>$like,'description'=>$like]);

        $gather = $pdo->prepare(
            'SELECT id,title,description,venue,lifecycle_status,starts_at
             FROM gather_events
             WHERE account_id=:account_id AND (title LIKE :title ESCAPE "\\\\" OR description LIKE :description ESCAPE "\\\\" OR venue LIKE :venue ESCAPE "\\\\")
             ORDER BY starts_at DESC LIMIT 20'
        );
        $gather->execute(['account_id'=>$accountId,'title'=>$like,'description'=>$like,'venue'=>$like]);

        $beacon = $pdo->prepare(
            'SELECT id,title,summary,page_type,visibility,updated_at
             FROM beacon_pages
             WHERE account_id=:account_id AND (title LIKE :title ESCAPE "\\\\" OR summary LIKE :summary ESCAPE "\\\\")
             ORDER BY updated_at DESC LIMIT 20'
        );
        $beacon->execute(['account_id'=>$accountId,'title'=>$like,'summary'=>$like]);

        $health = $pdo->prepare(
            'SELECT id,observed_on,energy_level,share_derived_fact,updated_at
             FROM health_wellbeing_checkins
             WHERE account_id=:account_id AND observed_on LIKE :observed_on ESCAPE "\\\\"
             ORDER BY observed_on DESC LIMIT 12'
        );
        $health->execute(['account_id'=>$accountId,'observed_on'=>$like]);

        $homeNotes = $pdo->prepare(
            'SELECT room_key,name,note_text,note_updated_at
             FROM healing_home_rooms
             WHERE account_id=:account_id AND note_text IS NOT NULL AND note_text <> "" AND note_text LIKE :note ESCAPE "\\\\"
             ORDER BY note_updated_at DESC LIMIT 12'
        );
        $homeNotes->execute(['account_id'=>$accountId,'note'=>$like]);

        $questRows = $this->withSnippets($quests->fetchAll(), 'description');
        $chronicleRows = $this->withSnippets($chronicle->fetchAll(), 'body');
        $worldRows = $this->withSnippets($worlds->fetchAll(), 'description');
        $gatherRows = $this->withSnippets($gather->fetchAll(), 'description');
        $beaconRows = $this->withSnippets($beacon->fetchAll(), 'summary');
        $healthRows = $health->fetchAll();
        $homeNoteRows = $this->withSnippets($homeNotes->fetchAll(), 'note_text');
        return [
            'query'=>$query,
            'quests'=>$questRows,
            'chronicle'=>$chronicleRows,
            'worlds'=>$worldRows,
            'gather'=>$gatherRows,
            'beacon'=>$beaconRows,
            'health'=>$healthRows,
            'home_notes'=>$homeNoteRows,
            'total'=>count($questRows)+count($chronicleRows)+count($worldRows)+count($gatherRows)+count($beaconRows)+count($healthRows)+count($homeNoteRows),
        ];
    }

    private function withSnippets(array $rows, string $field): array
    {
        foreach ($rows as &$row) {
            $text = trim(strip_tags((string)($row[$field] ?? '')));
            $row['snippet'] = mb_strlen($text) > 180 ? rtrim(mb_substr($text,0,177)) . '…' : $text;
            unset($row[$field]);
        }
        return $rows;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\','%','_'], ['\\\\','\\%','\\_'], $value);
    }
}
