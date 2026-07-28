CREATE TABLE hearth_layout_preferences (
    account_id CHAR(36) NOT NULL,
    widget_key ENUM('pillars','chronicle','world') NOT NULL,
    position SMALLINT UNSIGNED NOT NULL,
    visible TINYINT(1) NOT NULL DEFAULT 1,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (account_id, widget_key),
    UNIQUE KEY uq_hearth_layout_position (account_id, position),
    CONSTRAINT fk_hearth_layout_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO hearth_layout_preferences (account_id,widget_key,position,visible,updated_at)
SELECT id,'pillars',10,1,UTC_TIMESTAMP() FROM platform_accounts
ON DUPLICATE KEY UPDATE widget_key=VALUES(widget_key);
INSERT INTO hearth_layout_preferences (account_id,widget_key,position,visible,updated_at)
SELECT id,'chronicle',20,1,UTC_TIMESTAMP() FROM platform_accounts
ON DUPLICATE KEY UPDATE widget_key=VALUES(widget_key);
INSERT INTO hearth_layout_preferences (account_id,widget_key,position,visible,updated_at)
SELECT id,'world',30,1,UTC_TIMESTAMP() FROM platform_accounts
ON DUPLICATE KEY UPDATE widget_key=VALUES(widget_key);