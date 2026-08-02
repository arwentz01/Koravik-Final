CREATE TABLE IF NOT EXISTS platform_media_assets (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    owner_module VARCHAR(60) NOT NULL,
    owner_reference CHAR(36) NULL,
    original_name VARCHAR(255) NOT NULL,
    media_type VARCHAR(80) NOT NULL,
    storage_reference VARCHAR(500) NOT NULL,
    visibility ENUM('private','unlisted','public') NOT NULL DEFAULT 'private',
    alt_text VARCHAR(500) NULL,
    metadata_json JSON NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_media_owner (account_id, owner_module, owner_reference),
    CONSTRAINT fk_media_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS beacon_page_blocks (
    id CHAR(36) PRIMARY KEY,
    page_id CHAR(36) NOT NULL,
    block_type ENUM('text','link','contact','event','qr_action') NOT NULL,
    sort_order INT NOT NULL DEFAULT 10,
    title VARCHAR(180) NULL,
    body TEXT NULL,
    action_label VARCHAR(120) NULL,
    action_value VARCHAR(500) NULL,
    visibility ENUM('private','unlisted','public') NOT NULL DEFAULT 'private',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_beacon_blocks_page (page_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chronicle_reflection_reviews (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    source_module VARCHAR(60) NOT NULL,
    source_reference CHAR(36) NULL,
    title VARCHAR(180) NOT NULL,
    body TEXT NOT NULL,
    privacy ENUM('private','unlisted') NOT NULL DEFAULT 'private',
    status ENUM('proposed','saved','dismissed') NOT NULL DEFAULT 'proposed',
    chronicle_entry_id CHAR(36) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_reflection_reviews_account (account_id, status, created_at),
    CONSTRAINT fk_reflection_review_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
