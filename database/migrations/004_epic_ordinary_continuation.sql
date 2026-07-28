CREATE TABLE world_npcs (
    world_key VARCHAR(100) NOT NULL,
    npc_key VARCHAR(100) NOT NULL,
    name VARCHAR(120) NOT NULL,
    role_label VARCHAR(160) NOT NULL,
    description VARCHAR(500) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (world_key, npc_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO world_npcs (world_key, npc_key, name, role_label, description) VALUES
('epic-ordinary', 'caretaker', 'The Caretaker', 'Keeper of the Hearth', 'A steady witness who remembers what the Player chooses and what they carry through.');

CREATE TABLE world_relationships (
    installation_id CHAR(36) NOT NULL,
    npc_key VARCHAR(100) NOT NULL,
    trust_score INT NOT NULL DEFAULT 0,
    relationship_stage VARCHAR(40) NOT NULL DEFAULT 'new',
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (installation_id, npc_key),
    CONSTRAINT fk_world_relationship_installation FOREIGN KEY (installation_id) REFERENCES world_installations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE world_relationship_history (
    id CHAR(36) PRIMARY KEY,
    installation_id CHAR(36) NOT NULL,
    npc_key VARCHAR(100) NOT NULL,
    delta_value INT NOT NULL,
    reason_code VARCHAR(100) NOT NULL,
    source_event_id CHAR(36) NULL,
    explanation VARCHAR(500) NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_relationship_event_reason (installation_id, npc_key, source_event_id, reason_code),
    KEY idx_relationship_history (installation_id, npc_key, created_at),
    CONSTRAINT fk_relationship_history_installation FOREIGN KEY (installation_id) REFERENCES world_installations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE world_narrative_progress (
    installation_id CHAR(36) PRIMARY KEY,
    current_arc VARCHAR(100) NOT NULL DEFAULT 'coming-home',
    current_chapter VARCHAR(100) NOT NULL DEFAULT 'the-first-light',
    current_scene VARCHAR(100) NOT NULL DEFAULT 'caretaker-welcome',
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_narrative_progress_installation FOREIGN KEY (installation_id) REFERENCES world_installations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE world_choice_history (
    id CHAR(36) PRIMARY KEY,
    installation_id CHAR(36) NOT NULL,
    scene_key VARCHAR(100) NOT NULL,
    choice_key VARCHAR(100) NOT NULL,
    choice_label VARCHAR(180) NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_world_scene_choice (installation_id, scene_key),
    CONSTRAINT fk_world_choice_installation FOREIGN KEY (installation_id) REFERENCES world_installations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO world_relationships (installation_id, npc_key, trust_score, relationship_stage, updated_at)
SELECT wi.id, 'caretaker', 0, 'new', UTC_TIMESTAMP()
FROM world_installations wi
WHERE wi.world_key = 'epic-ordinary'
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

INSERT INTO world_narrative_progress (installation_id, current_arc, current_chapter, current_scene, updated_at)
SELECT wi.id, 'coming-home', 'the-first-light', 'caretaker-welcome', UTC_TIMESTAMP()
FROM world_installations wi
WHERE wi.world_key = 'epic-ordinary'
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
