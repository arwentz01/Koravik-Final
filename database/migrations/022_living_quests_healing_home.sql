ALTER TABLE quests ADD COLUMN purpose TEXT NULL AFTER description;
ALTER TABLE quests ADD COLUMN next_step VARCHAR(180) NULL AFTER purpose;
ALTER TABLE quests ADD COLUMN origin_type VARCHAR(40) NOT NULL DEFAULT 'personal' AFTER quest_type;
ALTER TABLE quests ADD COLUMN origin_reference VARCHAR(180) NULL AFTER origin_type;

CREATE TABLE IF NOT EXISTS quest_resolutions (
    id CHAR(36) PRIMARY KEY,
    quest_id CHAR(36) NOT NULL,
    account_id CHAR(36) NOT NULL,
    outcome VARCHAR(40) NOT NULL,
    reflection TEXT NULL,
    resolved_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_quest_resolutions_account_time (account_id, resolved_at),
    INDEX idx_quest_resolutions_quest_time (quest_id, resolved_at),
    CONSTRAINT fk_quest_resolutions_quest FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE,
    CONSTRAINT fk_quest_resolutions_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS healing_home_state (
    account_id CHAR(36) PRIMARY KEY,
    atmosphere VARCHAR(40) NOT NULL DEFAULT 'quiet_morning',
    current_room VARCHAR(80) NOT NULL DEFAULT 'entry_hall',
    last_returned_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_healing_home_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS healing_home_rooms (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    room_key VARCHAR(80) NOT NULL,
    name VARCHAR(120) NOT NULL,
    state VARCHAR(30) NOT NULL DEFAULT 'visible_locked',
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    unlocked_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_healing_home_room (account_id, room_key),
    INDEX idx_healing_home_rooms_order (account_id, sort_order),
    CONSTRAINT fk_healing_home_room_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
