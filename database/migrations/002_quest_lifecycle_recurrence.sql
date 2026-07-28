ALTER TABLE quests
    ADD COLUMN purpose VARCHAR(500) NULL AFTER description,
    ADD COLUMN lifecycle_status VARCHAR(20) NOT NULL DEFAULT 'active' AFTER status,
    ADD COLUMN archived_at DATETIME NULL AFTER updated_at;

CREATE TABLE IF NOT EXISTS quest_recurrence_rules (
    quest_id CHAR(36) PRIMARY KEY,
    frequency VARCHAR(20) NOT NULL,
    interval_count SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    starts_on DATE NOT NULL,
    ends_on DATE NULL,
    occurrence_limit INT UNSIGNED NULL,
    monthly_mode VARCHAR(30) NULL,
    day_of_month TINYINT UNSIGNED NULL,
    ordinal_week TINYINT NULL,
    ordinal_weekday TINYINT UNSIGNED NULL,
    timezone VARCHAR(80) NOT NULL DEFAULT 'America/New_York',
    generated_through DATE NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_quest_recurrence_quest FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE,
    INDEX idx_quest_recurrence_generation (frequency, generated_through, ends_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quest_recurrence_weekdays (
    quest_id CHAR(36) NOT NULL,
    weekday TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (quest_id, weekday),
    CONSTRAINT fk_quest_recurrence_weekday_quest FOREIGN KEY (quest_id) REFERENCES quest_recurrence_rules(quest_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quest_occurrences (
    id CHAR(36) PRIMARY KEY,
    quest_id CHAR(36) NOT NULL,
    account_id CHAR(36) NOT NULL,
    scheduled_for DATE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'scheduled',
    available_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    skipped_at DATETIME NULL,
    dismissed_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_quest_occurrence (quest_id, scheduled_for),
    INDEX idx_quest_occurrences_account_status_date (account_id, status, scheduled_for),
    CONSTRAINT fk_quest_occurrence_quest FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE,
    CONSTRAINT fk_quest_occurrence_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO quest_occurrences (id, quest_id, account_id, scheduled_for, status, available_at, completed_at, created_at, updated_at)
SELECT UUID(), q.id, q.account_id, DATE(q.created_at),
       CASE WHEN qc.id IS NULL THEN 'available' ELSE 'completed' END,
       q.created_at, qc.completed_at, q.created_at, COALESCE(qc.completed_at, q.updated_at)
FROM quests q
LEFT JOIN quest_completions qc ON qc.quest_id = q.id AND qc.account_id = q.account_id
WHERE NOT EXISTS (SELECT 1 FROM quest_occurrences qo WHERE qo.quest_id = q.id);