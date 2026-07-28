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
        if ($query === '') return ['query'=>'','quests'=>[],'chronicle'=>[],'worlds'=>[],'total'=>0];
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

        $questRows = $this->withSnippets($quests->fetchAll(), 'description');
        $chronicleRows = $this->withSnippets($chronicle->fetchAll(), 'body');
        $worldRows = $this->withSnippets($worlds->fetchAll(), 'description');
        return [
            'query'=>$query,
            'quests'=>$questRows,
            'chronicle'=>$chronicleRows,
            'worlds'=>$worldRows,
            'total'=>count($questRows)+count($chronicleRows)+count($worldRows),
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
