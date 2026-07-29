CREATE TABLE healing_home_changes (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    source_type VARCHAR(40) NOT NULL,
    source_id CHAR(36) NOT NULL,
    change_key VARCHAR(100) NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    room_key VARCHAR(80) NOT NULL DEFAULT 'fireplace',
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_home_change_source (account_id, source_type, source_id, change_key),
    INDEX idx_home_changes_account_time (account_id, created_at),
    CONSTRAINT fk_home_changes_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE healing_home_keepsakes (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    source_type VARCHAR(40) NOT NULL,
    source_id CHAR(36) NOT NULL,
    keepsake_key VARCHAR(100) NOT NULL,
    name VARCHAR(180) NOT NULL,
    meaning TEXT NOT NULL,
    room_key VARCHAR(80) NOT NULL DEFAULT 'fireplace',
    displayed TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_home_keepsake_source (account_id, source_type, source_id, keepsake_key),
    INDEX idx_home_keepsakes_account_room (account_id, room_key, displayed),
    CONSTRAINT fk_home_keepsakes_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE journey_relationships (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    world_key VARCHAR(100) NOT NULL,
    character_key VARCHAR(100) NOT NULL,
    character_name VARCHAR(120) NOT NULL,
    relationship_state VARCHAR(40) NOT NULL DEFAULT 'new',
    familiarity SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    last_met_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_journey_relationship (account_id, world_key, character_key),
    INDEX idx_journey_relationships_account (account_id, world_key),
    CONSTRAINT fk_journey_relationship_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE journey_relationship_memories (
    id CHAR(36) PRIMARY KEY,
    relationship_id CHAR(36) NOT NULL,
    account_id CHAR(36) NOT NULL,
    source_type VARCHAR(40) NOT NULL,
    source_id CHAR(36) NOT NULL,
    memory_kind VARCHAR(40) NOT NULL,
    summary VARCHAR(500) NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_relationship_memory_source (relationship_id, source_type, source_id, memory_kind),
    INDEX idx_relationship_memories_time (relationship_id, created_at),
    CONSTRAINT fk_relationship_memory_relationship FOREIGN KEY (relationship_id) REFERENCES journey_relationships(id) ON DELETE CASCADE,
    CONSTRAINT fk_relationship_memory_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;