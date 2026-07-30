<?php

declare(strict_types=1);

namespace Koravik\Worlds;

use Koravik\Platform\Database\Database;
use PDO;

final class WorldHomeRepository
{
    public function __construct(private readonly Database $database)
    {
    }

    public function activeWorld(string $accountId): ?array
    {
        $statement = $this->database->pdo()->prepare(
            'SELECT c.world_key, c.name, c.tagline, i.id AS installation_id, i.status,
                    n.current_arc, n.current_chapter, n.current_scene,
                    r.relationship_stage, r.trust_score,
                    (SELECT COUNT(*)
                       FROM world_reactions reaction
                       LEFT JOIN world_reaction_reviews review ON review.reaction_id = reaction.id
                      WHERE reaction.installation_id = i.id AND review.reaction_id IS NULL) AS unread_reaction_count
               FROM world_installations i
               JOIN world_catalog c ON c.world_key = i.world_key
               LEFT JOIN world_narrative_progress n ON n.installation_id = i.id
               LEFT JOIN world_relationships r ON r.installation_id = i.id AND r.npc_key = "caretaker"
              WHERE i.account_id = :account_id AND i.status = "active"
              ORDER BY i.installed_at DESC
              LIMIT 1'
        );
        $statement->execute(['account_id' => $accountId]);
        $world = $statement->fetch();

        return $world ?: null;
    }

    public function reactions(string $accountId, int $limit = 6): array
    {
        $statement = $this->database->pdo()->prepare(
            'SELECT reaction.id, reaction.title, reaction.message, reaction.explanation,
                    reaction.source_fact_summary,
                    COALESCE(reaction.interpreted_at, reaction.created_at) AS interpreted_at,
                    review.reviewed_at, catalog.name AS world_name, installation.world_key
               FROM world_reactions reaction
               JOIN world_installations installation ON installation.id = reaction.installation_id
               JOIN world_catalog catalog ON catalog.world_key = installation.world_key
               LEFT JOIN world_reaction_reviews review ON review.reaction_id = reaction.id
              WHERE installation.account_id = :account_id
              ORDER BY (review.reviewed_at IS NULL) DESC, reaction.created_at DESC
              LIMIT :result_limit'
        );
        $statement->bindValue(':account_id', $accountId);
        $statement->bindValue(':result_limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function catalog(string $accountId): array
    {
        $statement = $this->database->pdo()->prepare(
            'SELECT catalog.world_key, catalog.name, catalog.tagline, catalog.package_version,
                    installation.status AS installation_status
               FROM world_catalog catalog
               LEFT JOIN world_installations installation
                 ON installation.world_key = catalog.world_key AND installation.account_id = :account_id
              WHERE catalog.status = "available"
              ORDER BY catalog.name'
        );
        $statement->execute(['account_id' => $accountId]);

        return $statement->fetchAll();
    }

    public function ownsReaction(string $accountId, string $reactionId): bool
    {
        $statement = $this->database->pdo()->prepare(
            'SELECT 1
               FROM world_reactions reaction
               JOIN world_installations installation ON installation.id = reaction.installation_id
              WHERE reaction.id = :reaction_id AND installation.account_id = :account_id
              LIMIT 1'
        );
        $statement->execute(['reaction_id' => $reactionId, 'account_id' => $accountId]);

        return (bool) $statement->fetchColumn();
    }

    public function markReviewed(PDO $pdo, string $accountId, string $reactionId): void
    {
        $statement = $pdo->prepare(
            'INSERT INTO world_reaction_reviews (reaction_id, account_id, reviewed_at)
             VALUES (:reaction_id, :account_id, UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE reviewed_at = reviewed_at'
        );
        $statement->execute(['reaction_id' => $reactionId, 'account_id' => $accountId]);
    }
}
