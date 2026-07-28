CREATE TABLE pillar_definitions (
    pillar_key VARCHAR(64) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO pillar_definitions (pillar_key, name, description, sort_order) VALUES
('health', 'Health', 'Care for body, mind, and sustainable wellbeing.', 10),
('home', 'Home', 'Create a steadier, more supportive living environment.', 20),
('relationships', 'Relationships', 'Nurture meaningful connection and belonging.', 30),
('growth', 'Growth', 'Learn, practice, and become more capable.', 40),
('purpose', 'Purpose', 'Move toward work and commitments that matter.', 50),
('community', 'Community', 'Participate in and care for the wider world.', 60),
('rest', 'Rest & Recreation', 'Recover, play, and make room for enjoyment.', 70),
('finance', 'Finance', 'Build clarity and stability around resources.', 80);

CREATE TABLE quest_pillar_links (
    quest_id CHAR(36) NOT NULL,
    pillar_key VARCHAR(64) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (quest_id, pillar_key),
    CONSTRAINT fk_quest_pillar_quest FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE,
    CONSTRAINT fk_quest_pillar_definition FOREIGN KEY (pillar_key) REFERENCES pillar_definitions(pillar_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pillar_contributions (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    pillar_key VARCHAR(64) NOT NULL,
    quest_id CHAR(36) NOT NULL,
    occurrence_id CHAR(36) NOT NULL,
    source_event_id CHAR(36) NOT NULL,
    status ENUM('active','reversed') NOT NULL DEFAULT 'active',
    contributed_on DATE NOT NULL,
    created_at DATETIME NOT NULL,
    reversed_at DATETIME NULL,
    UNIQUE KEY uq_pillar_source (source_event_id, pillar_key),
    KEY idx_pillar_account (account_id, pillar_key, contributed_on),
    CONSTRAINT fk_pillar_contribution_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE,
    CONSTRAINT fk_pillar_contribution_definition FOREIGN KEY (pillar_key) REFERENCES pillar_definitions(pillar_key),
    CONSTRAINT fk_pillar_contribution_quest FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE,
    CONSTRAINT fk_pillar_contribution_occurrence FOREIGN KEY (occurrence_id) REFERENCES quest_occurrences(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE chronicle_entries (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    entry_type ENUM('system','reflection','world') NOT NULL,
    title VARCHAR(180) NOT NULL,
    body TEXT NOT NULL,
    source_event_id CHAR(36) NULL,
    quest_id CHAR(36) NULL,
    occurrence_id CHAR(36) NULL,
    status ENUM('active','reversed') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    reversed_at DATETIME NULL,
    UNIQUE KEY uq_chronicle_source_type (source_event_id, entry_type),
    KEY idx_chronicle_account (account_id, created_at),
    CONSTRAINT fk_chronicle_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE,
    CONSTRAINT fk_chronicle_quest FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE SET NULL,
    CONSTRAINT fk_chronicle_occurrence FOREIGN KEY (occurrence_id) REFERENCES quest_occurrences(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE platform_consumer_receipts (
    consumer_key VARCHAR(100) NOT NULL,
    event_id CHAR(36) NOT NULL,
    consumed_at DATETIME NOT NULL,
    PRIMARY KEY (consumer_key, event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;