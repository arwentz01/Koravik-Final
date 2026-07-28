CREATE TABLE account_exports (
  id CHAR(36) PRIMARY KEY,
  account_id CHAR(36) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'requested',
  format VARCHAR(20) NOT NULL,
  manifest_json JSON NULL,
  export_json JSON NULL,
  requested_at DATETIME NOT NULL,
  completed_at DATETIME NULL,
  expires_at DATETIME NULL,
  downloaded_at DATETIME NULL,
  INDEX idx_account_export_account (account_id,status,requested_at),
  CONSTRAINT fk_account_export_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE account_closures (
  id CHAR(36) PRIMARY KEY,
  account_id CHAR(36) NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'pending_cancellation',
  confirmation_phrase VARCHAR(100) NOT NULL,
  requested_at DATETIME NOT NULL,
  cancellable_until DATETIME NOT NULL,
  processing_started_at DATETIME NULL,
  completed_at DATETIME NULL,
  retention_ledger_json JSON NULL,
  UNIQUE KEY uq_account_open_closure (account_id,status),
  INDEX idx_account_closure_due (status,cancellable_until),
  CONSTRAINT fk_account_closure_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE account_closure_steps (
  closure_id CHAR(36) NOT NULL,
  owner_module VARCHAR(80) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  outcome_summary VARCHAR(500) NULL,
  processed_at DATETIME NULL,
  PRIMARY KEY (closure_id,owner_module),
  CONSTRAINT fk_closure_step_closure FOREIGN KEY (closure_id) REFERENCES account_closures(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
