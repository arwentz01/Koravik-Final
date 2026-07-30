CREATE TABLE platform_form_drafts (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    form_key VARCHAR(120) NOT NULL,
    payload_json JSON NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_form_draft_account_key (account_id, form_key),
    INDEX idx_form_drafts_expiry (expires_at),
    CONSTRAINT fk_form_drafts_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE platform_idempotency_keys (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    action_key VARCHAR(160) NOT NULL,
    request_key CHAR(64) NOT NULL,
    response_json JSON NULL,
    created_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    UNIQUE KEY uq_idempotency_account_action_request (account_id, action_key, request_key),
    INDEX idx_idempotency_expiry (expires_at),
    CONSTRAINT fk_idempotency_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE auth_sessions (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    session_hash CHAR(64) NOT NULL UNIQUE,
    user_agent VARCHAR(255) NULL,
    ip_address VARCHAR(64) NULL,
    created_at DATETIME NOT NULL,
    last_seen_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    INDEX idx_auth_sessions_account_active (account_id, revoked_at, last_seen_at),
    CONSTRAINT fk_auth_sessions_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
