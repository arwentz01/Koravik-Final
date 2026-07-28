CREATE TABLE world_scenes (
    id CHAR(36) PRIMARY KEY,
    installation_id CHAR(36) NOT NULL,
    source_event_id CHAR(36) NOT NULL,
    scene_key VARCHAR(100) NOT NULL,
    title VARCHAR(180) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('open','chosen','reversed') NOT NULL DEFAULT 'open',
    chosen_choice_key VARCHAR(64) NULL,
    created_at DATETIME NOT NULL,
    chosen_at DATETIME NULL,
    reversed_at DATETIME NULL,
    UNIQUE KEY uq_world_scene_event (installation_id, source_event_id),
    KEY idx_world_scene_status (installation_id, status, created_at),
    CONSTRAINT fk_world_scene_installation FOREIGN KEY (installation_id) REFERENCES world_installations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE world_scene_choices (
    scene_id CHAR(36) NOT NULL,
    choice_key VARCHAR(64) NOT NULL,
    label VARCHAR(220) NOT NULL,
    response_text TEXT NOT NULL,
    relationship_delta SMALLINT NOT NULL DEFAULT 0,
    sort_order SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (scene_id, choice_key),
    CONSTRAINT fk_world_choice_scene FOREIGN KEY (scene_id) REFERENCES world_scenes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE world_relationship_entries (
    id CHAR(36) PRIMARY KEY,
    installation_id CHAR(36) NOT NULL,
    npc_key VARCHAR(100) NOT NULL,
    scene_id CHAR(36) NOT NULL,
    source_event_id CHAR(36) NOT NULL,
    choice_key VARCHAR(64) NOT NULL,
    relationship_delta SMALLINT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    status ENUM('active','reversed') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    reversed_at DATETIME NULL,
    UNIQUE KEY uq_world_relationship_scene (scene_id),
    KEY idx_world_relationship_npc (installation_id, npc_key, status),
    CONSTRAINT fk_world_relationship_installation FOREIGN KEY (installation_id) REFERENCES world_installations(id) ON DELETE CASCADE,
    CONSTRAINT fk_world_relationship_scene FOREIGN KEY (scene_id) REFERENCES world_scenes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;