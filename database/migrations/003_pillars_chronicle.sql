CREATE TABLE IF NOT EXISTS pillars (
    `key` VARCHAR(40) PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    description VARCHAR(255) NOT NULL,
    sort_order TINYINT UNSIGNED NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_pillars_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO pillars (`key`, name, description, sort_order) VALUES
('well-being', 'Well-being', 'Care for body, mind, rest, and resilience.', 1),
('growth', 'Growth', 'Learn, practice, and become more capable.', 2),
('connection', 'Connection', 'Strengthen relationships and belonging.', 3),
('home', 'Home', 'Create a supportive place and daily environment.', 4),
('stability', 'Stability', 'Support practical, financial, and personal steadiness.', 5),
('purpose', 'Purpose', 'Contribute, serve, and move toward what matters.', 6),
('creativity', 'Creativity', 'Make, express, imagine, and explore ideas.', 7),
('adventure', 'Adventure', 'Experience novelty, play, courage, and discovery.', 8)
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), sort_order = VALUES(sort_order);

ALTER TABLE quests
    ADD COLUMN quest_type VARCHAR(30) NOT NULL DEFAULT 'action' AFTER purpose,
    ADD COLUMN pillar_key VARCHAR(40) NULL AFTER quest_type,
    ADD CONSTRAINT fk_quests_pillar FOREIGN KEY (pillar_key) REFERENCES pillars(`key`) ON DELETE SET NULL,
    ADD INDEX idx_quests_account_pillar (account_id, pillar_key);

CREATE TABLE IF NOT EXISTS quest_reflections (
    id CHAR(36) PRIMARY KEY,
    occurrence_id CHAR(36) NOT NULL,
    quest_id CHAR(36) NOT NULL,
    account_id CHAR(36) NOT NULL,
    reflection_text TEXT NOT NULL,
    mood VARCHAR(30) NULL,
    chronicle_entry_id CHAR(36) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_quest_reflection_occurrence (occurrence_id),
    INDEX idx_quest_reflections_account_created (account_id, created_at),
    CONSTRAINT fk_quest_reflection_occurrence FOREIGN KEY (occurrence_id) REFERENCES quest_occurrences(id) ON DELETE CASCADE,
    CONSTRAINT fk_quest_reflection_quest FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE,
    CONSTRAINT fk_quest_reflection_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chronicle_entries (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    title VARCHAR(180) NOT NULL,
    body TEXT NOT NULL,
    visibility VARCHAR(20) NOT NULL DEFAULT 'private',
    entry_type VARCHAR(30) NOT NULL DEFAULT 'reflection',
    occurred_on DATE NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_chronicle_account_date (account_id, occurred_on, created_at),
    CONSTRAINT fk_chronicle_entry_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chronicle_source_references (
    id CHAR(36) PRIMARY KEY,
    entry_id CHAR(36) NOT NULL,
    source_module VARCHAR(40) NOT NULL,
    source_type VARCHAR(40) NOT NULL,
    source_id CHAR(36) NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_chronicle_source (entry_id, source_module, source_type, source_id),
    INDEX idx_chronicle_source_lookup (source_module, source_type, source_id),
    CONSTRAINT fk_chronicle_source_entry FOREIGN KEY (entry_id) REFERENCES chronicle_entries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE quest_reflections
    ADD CONSTRAINT fk_quest_reflection_chronicle FOREIGN KEY (chronicle_entry_id) REFERENCES chronicle_entries(id) ON DELETE SET NULL;
