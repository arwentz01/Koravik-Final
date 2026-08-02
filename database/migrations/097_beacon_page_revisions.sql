ALTER TABLE beacon_pages MODIFY id CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;

CREATE TABLE IF NOT EXISTS beacon_page_revisions (
    id CHAR(36) PRIMARY KEY,
    page_id CHAR(36) NOT NULL,
    changed_by_account_id CHAR(36) NOT NULL,
    action VARCHAR(40) NOT NULL,
    snapshot_json JSON NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_beacon_page_revision (page_id, created_at),
    CONSTRAINT fk_beacon_page_revision_page FOREIGN KEY (page_id) REFERENCES beacon_pages(id) ON DELETE CASCADE,
    CONSTRAINT fk_beacon_page_revision_account FOREIGN KEY (changed_by_account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
