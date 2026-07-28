CREATE TABLE world_story_threads (
    installation_id CHAR(36) PRIMARY KEY,
    story_key VARCHAR(100) NOT NULL,
    chapter SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    progress_count INT UNSIGNED NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_story_thread_installation FOREIGN KEY (installation_id) REFERENCES world_installations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE world_relationships (
    installation_id CHAR(36) NOT NULL,
    character_key VARCHAR(100) NOT NULL,
    relationship_stage VARCHAR(40) NOT NULL DEFAULT 'new_acquaintance',
    trust_count INT UNSIGNED NOT NULL DEFAULT 0,
    last_interaction_at DATETIME NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (installation_id, character_key),
    CONSTRAINT fk_world_relationship_installation FOREIGN KEY (installation_id) REFERENCES world_installations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE world_story_moments (
    id CHAR(36) PRIMARY KEY,
    installation_id CHAR(36) NOT NULL,
    source_event_id CHAR(36) NOT NULL,
    source_completion_event_id CHAR(36) NOT NULL,
    story_key VARCHAR(100) NOT NULL,
    chapter SMALLINT UNSIGNED NOT NULL,
    character_key VARCHAR(100) NOT NULL,
    relationship_stage VARCHAR(40) NOT NULL,
    title VARCHAR(180) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('active','reversed') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    reversed_at DATETIME NULL,
    UNIQUE KEY uq_story_moment_source (installation_id, source_completion_event_id),
    KEY idx_story_moment_timeline (installation_id, created_at),
    CONSTRAINT fk_story_moment_installation FOREIGN KEY (installation_id) REFERENCES world_installations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO world_story_threads (installation_id, story_key, chapter, progress_count, status, updated_at)
SELECT id, 'caretaker-path', 1, 0, 'active', UTC_TIMESTAMP()
FROM world_installations
WHERE world_key = 'epic-ordinary'
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

INSERT INTO world_relationships (installation_id, character_key, relationship_stage, trust_count, updated_at)
SELECT id, 'caretaker', 'new_acquaintance', 0, UTC_TIMESTAMP()
FROM world_installations
WHERE world_key = 'epic-ordinary'
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);