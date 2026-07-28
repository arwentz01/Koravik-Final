CREATE TABLE account_activity (
    account_id CHAR(36) PRIMARY KEY,
    last_seen_at DATETIME NULL,
    returned_at DATETIME NULL,
    return_pending TINYINT(1) NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_account_activity_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE quest_occurrences
    ADD COLUMN rescheduled_from DATE NULL AFTER scheduled_for;

INSERT INTO world_fact_permissions
(installation_id, fact_key, granted, explanation, granted_at, updated_at)
SELECT id, 'player.returned', 1, 'Allows Epic Ordinary to receive a minimized fact that the player returned after an extended absence. No Quest details are included.', installed_at, UTC_TIMESTAMP()
FROM world_installations
WHERE world_key = 'epic-ordinary'
ON DUPLICATE KEY UPDATE explanation = VALUES(explanation), updated_at = VALUES(updated_at);