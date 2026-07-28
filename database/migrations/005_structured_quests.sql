ALTER TABLE quests
    ADD COLUMN quest_type VARCHAR(30) NOT NULL DEFAULT 'action' AFTER description;

CREATE TABLE quest_steps (
    id CHAR(36) PRIMARY KEY,
    quest_id CHAR(36) NOT NULL,
    title VARCHAR(180) NOT NULL,
    is_required TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT UNSIGNED NOT NULL,
    status ENUM('pending','completed','skipped') NOT NULL DEFAULT 'pending',
    completed_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_quest_steps_order (quest_id, sort_order),
    CONSTRAINT fk_quest_steps_quest FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE quest_milestones (
    id CHAR(36) PRIMARY KEY,
    quest_id CHAR(36) NOT NULL,
    title VARCHAR(180) NOT NULL,
    threshold_percent TINYINT UNSIGNED NOT NULL,
    reached_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_quest_milestone_threshold (quest_id, threshold_percent),
    CONSTRAINT fk_quest_milestones_quest FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;