CREATE TABLE IF NOT EXISTS beacon_campaigns (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    page_id CHAR(36) NULL,
    short_link_id CHAR(36) NULL,
    title VARCHAR(180) NOT NULL,
    purpose VARCHAR(500) NULL,
    audience VARCHAR(180) NULL,
    status ENUM('draft','active','paused','archived') NOT NULL DEFAULT 'draft',
    engagement_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_beacon_campaign_owner (account_id, status, created_at),
    CONSTRAINT fk_beacon_campaign_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gather_event_followups (
    id CHAR(36) PRIMARY KEY,
    event_id CHAR(36) NOT NULL,
    author_account_id CHAR(36) NOT NULL,
    title VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    audience ENUM('all','confirmed','attended','volunteers') NOT NULL DEFAULT 'confirmed',
    status ENUM('draft','sent','archived') NOT NULL DEFAULT 'draft',
    created_chronicle_proposal TINYINT(1) NOT NULL DEFAULT 0,
    created_quest_proposal TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    sent_at DATETIME NULL,
    KEY idx_gather_followup_event (event_id, created_at),
    CONSTRAINT fk_gather_followup_author FOREIGN KEY (author_account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
