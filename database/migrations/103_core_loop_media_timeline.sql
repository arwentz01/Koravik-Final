CREATE TABLE IF NOT EXISTS platform_media_links (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    media_asset_id CHAR(36) NOT NULL,
    owner_module ENUM('Quests','Chronicle','Gather','Beacon','Health') NOT NULL,
    owner_record_id CHAR(36) NOT NULL,
    purpose VARCHAR(180) NULL,
    created_at DATETIME NOT NULL,
    KEY idx_media_links_owner (account_id, owner_module, owner_record_id),
    KEY idx_media_links_asset (media_asset_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quest_timeline_events (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    quest_id CHAR(36) NOT NULL,
    event_type VARCHAR(80) NOT NULL,
    summary VARCHAR(500) NOT NULL,
    source_type VARCHAR(80) NULL,
    source_id CHAR(36) NULL,
    occurred_at DATETIME NOT NULL,
    KEY idx_quest_timeline (account_id, quest_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
