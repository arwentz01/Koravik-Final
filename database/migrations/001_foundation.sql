CREATE TABLE IF NOT EXISTS schema_migrations (
    version VARCHAR(64) PRIMARY KEY,
    applied_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS platform_accounts (
    id CHAR(36) PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    display_name VARCHAR(120) NOT NULL,
    role VARCHAR(40) NOT NULL DEFAULT 'user',
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auth_credentials (
    account_id CHAR(36) PRIMARY KEY,
    password_hash VARCHAR(255) NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_auth_credentials_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quests (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_quests_account_status (account_id, status),
    CONSTRAINT fk_quests_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quest_completions (
    id CHAR(36) PRIMARY KEY,
    quest_id CHAR(36) NOT NULL,
    account_id CHAR(36) NOT NULL,
    completed_at DATETIME NOT NULL,
    UNIQUE KEY uq_quest_completion (quest_id, account_id),
    CONSTRAINT fk_quest_completions_quest FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE,
    CONSTRAINT fk_quest_completions_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS platform_outbox (
    id CHAR(36) PRIMARY KEY,
    event_name VARCHAR(160) NOT NULL,
    event_version SMALLINT UNSIGNED NOT NULL,
    account_id CHAR(36) NOT NULL,
    payload_json JSON NOT NULL,
    status VARCHAR(20) NOT NULL,
    attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    available_at DATETIME NOT NULL,
    occurred_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    locked_at DATETIME NULL,
    delivered_at DATETIME NULL,
    last_error VARCHAR(500) NULL,
    INDEX idx_outbox_delivery (status, available_at, created_at),
    CONSTRAINT fk_outbox_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS world_installations (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    world_key VARCHAR(100) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    installed_at DATETIME NOT NULL,
    UNIQUE KEY uq_world_installation (account_id, world_key),
    CONSTRAINT fk_world_installations_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS world_state (
    installation_id CHAR(36) NOT NULL,
    state_key VARCHAR(160) NOT NULL,
    state_json JSON NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (installation_id, state_key),
    CONSTRAINT fk_world_state_installation FOREIGN KEY (installation_id) REFERENCES world_installations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS world_reactions (
    id CHAR(36) PRIMARY KEY,
    installation_id CHAR(36) NOT NULL,
    source_event_id CHAR(36) NOT NULL,
    title VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    explanation TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_world_reaction_event (installation_id, source_event_id),
    INDEX idx_world_reactions_created (installation_id, created_at),
    CONSTRAINT fk_world_reactions_installation FOREIGN KEY (installation_id) REFERENCES world_installations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS world_event_receipts (
    event_id CHAR(36) PRIMARY KEY,
    installation_id CHAR(36) NOT NULL,
    consumed_at DATETIME NOT NULL,
    CONSTRAINT fk_world_receipts_installation FOREIGN KEY (installation_id) REFERENCES world_installations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_log (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NULL,
    action VARCHAR(120) NOT NULL,
    subject_type VARCHAR(80) NOT NULL,
    subject_id CHAR(36) NULL,
    occurred_at DATETIME NOT NULL,
    INDEX idx_audit_account_time (account_id, occurred_at),
    CONSTRAINT fk_audit_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
