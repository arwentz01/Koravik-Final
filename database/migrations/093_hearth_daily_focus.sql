CREATE TABLE hearth_daily_focus (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    focus_date DATE NOT NULL,
    intention VARCHAR(180) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_hearth_daily_focus_account_date (account_id, focus_date),
    INDEX idx_hearth_daily_focus_account_date (account_id, focus_date),
    CONSTRAINT fk_hearth_daily_focus_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hearth_daily_focus_entries (
    focus_id CHAR(36) NOT NULL,
    quest_occurrence_id CHAR(36) NOT NULL,
    position TINYINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (focus_id, quest_occurrence_id),
    UNIQUE KEY uq_hearth_daily_focus_position (focus_id, position),
    INDEX idx_hearth_daily_focus_occurrence (quest_occurrence_id),
    CONSTRAINT fk_hearth_daily_focus_entry_focus FOREIGN KEY (focus_id) REFERENCES hearth_daily_focus(id) ON DELETE CASCADE,
    CONSTRAINT fk_hearth_daily_focus_entry_occurrence FOREIGN KEY (quest_occurrence_id) REFERENCES quest_occurrences(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
