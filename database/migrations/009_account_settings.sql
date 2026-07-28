CREATE TABLE account_settings (
    account_id CHAR(36) PRIMARY KEY,
    appearance ENUM('system','light','dark') NOT NULL DEFAULT 'system',
    reduced_motion TINYINT(1) NOT NULL DEFAULT 0,
    high_contrast TINYINT(1) NOT NULL DEFAULT 0,
    timezone VARCHAR(64) NOT NULL DEFAULT 'America/New_York',
    date_format ENUM('month_day_year','year_month_day','day_month_year') NOT NULL DEFAULT 'month_day_year',
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_account_settings_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO account_settings (account_id, updated_at)
SELECT id, UTC_TIMESTAMP() FROM platform_accounts
ON DUPLICATE KEY UPDATE updated_at = account_settings.updated_at;