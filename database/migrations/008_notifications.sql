CREATE TABLE notification_preferences (
    account_id CHAR(36) NOT NULL,
    category VARCHAR(80) NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (account_id, category),
    CONSTRAINT fk_notification_preference_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    source_module VARCHAR(80) NOT NULL,
    category VARCHAR(80) NOT NULL,
    title VARCHAR(180) NOT NULL,
    body VARCHAR(500) NOT NULL,
    target_url VARCHAR(255) NOT NULL,
    reason VARCHAR(500) NOT NULL,
    source_event_id CHAR(36) NULL,
    read_at DATETIME NULL,
    dismissed_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_notification_event_category (account_id, source_event_id, category),
    INDEX idx_notifications_account_state_created (account_id, dismissed_at, read_at, created_at),
    CONSTRAINT fk_notification_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO notification_preferences (account_id, category, enabled, updated_at)
SELECT id, 'world.reactions', 1, UTC_TIMESTAMP() FROM platform_accounts
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);

INSERT INTO notification_preferences (account_id, category, enabled, updated_at)
SELECT id, 'platform.return', 1, UTC_TIMESTAMP() FROM platform_accounts
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
