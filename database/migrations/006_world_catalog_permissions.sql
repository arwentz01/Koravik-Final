CREATE TABLE world_catalog (
    world_key VARCHAR(100) PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    tagline VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    content_notice VARCHAR(500) NULL,
    accessibility_notes VARCHAR(500) NOT NULL,
    package_version VARCHAR(40) NOT NULL,
    status ENUM('available','retired') NOT NULL DEFAULT 'available',
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE world_fact_permissions (
    installation_id CHAR(36) NOT NULL,
    fact_key VARCHAR(120) NOT NULL,
    granted TINYINT(1) NOT NULL DEFAULT 0,
    explanation VARCHAR(500) NOT NULL,
    granted_at DATETIME NULL,
    revoked_at DATETIME NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (installation_id, fact_key),
    CONSTRAINT fk_world_fact_permission_installation FOREIGN KEY (installation_id) REFERENCES world_installations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO world_catalog
(world_key, name, tagline, description, content_notice, accessibility_notes, package_version, status, created_at)
VALUES
('epic-ordinary', 'Epic Ordinary', 'A quiet world that notices the life you are already living.', 'Meet the Caretaker and let ordinary real-life actions shape a gentle, persistent story. Epic Ordinary interprets only the fact categories you explicitly permit.', 'Includes reflective language about effort, rest, relationships, and personal growth. It does not use punishment, streak loss, or failure language.', 'Server-rendered and keyboard accessible. No motion is required. World reactions include a plain-language explanation of what changed and why.', '1.0.0', 'available', UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE
name = VALUES(name), tagline = VALUES(tagline), description = VALUES(description), content_notice = VALUES(content_notice), accessibility_notes = VALUES(accessibility_notes), package_version = VALUES(package_version), status = VALUES(status);

INSERT INTO world_fact_permissions
(installation_id, fact_key, granted, explanation, granted_at, updated_at)
SELECT id, 'quest.completed', 1, 'Allows Epic Ordinary to receive a minimized fact when a Quest occurrence is completed. Quest notes and full private records are not shared.', installed_at, UTC_TIMESTAMP()
FROM world_installations
WHERE world_key = 'epic-ordinary'
ON DUPLICATE KEY UPDATE explanation = VALUES(explanation), updated_at = VALUES(updated_at);
