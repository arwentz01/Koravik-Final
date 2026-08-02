CREATE TABLE IF NOT EXISTS health_wellbeing_checkins (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    observed_on DATE NOT NULL,
    energy_level TINYINT UNSIGNED NOT NULL,
    feeling_word ENUM('calm','strained','hopeful','tired','steady') NOT NULL,
    private_note VARCHAR(1000) NULL,
    share_derived_fact TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_health_checkin_day (account_id, observed_on),
    INDEX idx_health_checkin_history (account_id, observed_on),
    CONSTRAINT fk_health_checkin_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE,
    CONSTRAINT chk_health_energy CHECK (energy_level BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS health_checkin_revisions (
    id CHAR(36) PRIMARY KEY,
    checkin_id CHAR(36) NULL,
    account_id CHAR(36) NOT NULL,
    action ENUM('created','corrected','deleted') NOT NULL,
    snapshot_json JSON NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_health_checkin_revision (checkin_id, created_at),
    CONSTRAINT fk_health_revision_checkin FOREIGN KEY (checkin_id) REFERENCES health_wellbeing_checkins(id) ON DELETE SET NULL,
    CONSTRAINT fk_health_revision_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
