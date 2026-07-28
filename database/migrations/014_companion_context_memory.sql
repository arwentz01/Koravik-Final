CREATE TABLE companion_context_permissions (
  account_id CHAR(36) NOT NULL,
  context_key VARCHAR(60) NOT NULL,
  allowed TINYINT(1) NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (account_id,context_key),
  CONSTRAINT fk_companion_context_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE companion_memories (
  id CHAR(36) PRIMARY KEY,
  account_id CHAR(36) NOT NULL,
  memory_text VARCHAR(500) NOT NULL,
  provenance VARCHAR(500) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'active',
  last_used_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_companion_memory_account (account_id,status,created_at),
  CONSTRAINT fk_companion_memory_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE companion_context_uses (
  id CHAR(36) PRIMARY KEY,
  account_id CHAR(36) NOT NULL,
  proposal_id CHAR(36) NULL,
  source_module VARCHAR(60) NOT NULL,
  source_type VARCHAR(60) NOT NULL,
  source_id CHAR(36) NULL,
  minimized_summary VARCHAR(1000) NOT NULL,
  use_scope VARCHAR(20) NOT NULL DEFAULT 'once',
  created_at DATETIME NOT NULL,
  INDEX idx_companion_context_use (account_id,created_at),
  CONSTRAINT fk_companion_context_use_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
