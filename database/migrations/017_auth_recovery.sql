CREATE TABLE auth_security_state (
  account_id CHAR(36) PRIMARY KEY,
  failed_attempts INT NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  session_version INT NOT NULL DEFAULT 1,
  last_failed_at DATETIME NULL,
  last_success_at DATETIME NULL,
  updated_at DATETIME NOT NULL,
  CONSTRAINT fk_auth_security_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO auth_security_state (account_id,updated_at)
SELECT id,UTC_TIMESTAMP() FROM platform_accounts
ON DUPLICATE KEY UPDATE updated_at=updated_at;

CREATE TABLE auth_recovery_tokens (
  id CHAR(36) PRIMARY KEY,
  account_id CHAR(36) NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  requested_ip_hash CHAR(64) NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_recovery_account (account_id,created_at),
  INDEX idx_recovery_expiry (expires_at,used_at),
  CONSTRAINT fk_recovery_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE auth_delivery_messages (
  id CHAR(36) PRIMARY KEY,
  account_id CHAR(36) NOT NULL,
  channel VARCHAR(30) NOT NULL DEFAULT 'email',
  recipient VARCHAR(255) NOT NULL,
  template_key VARCHAR(80) NOT NULL,
  payload_json JSON NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'queued',
  created_at DATETIME NOT NULL,
  delivered_at DATETIME NULL,
  INDEX idx_auth_delivery_status (status,created_at),
  CONSTRAINT fk_auth_delivery_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;