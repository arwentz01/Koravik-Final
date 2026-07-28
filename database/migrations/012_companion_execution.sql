ALTER TABLE companion_proposals
  ADD COLUMN executed_module VARCHAR(80) NULL AFTER dismissed_at,
  ADD COLUMN executed_record_id CHAR(36) NULL AFTER executed_module,
  ADD COLUMN executed_at DATETIME NULL AFTER executed_record_id,
  ADD COLUMN failure_message VARCHAR(500) NULL AFTER executed_at,
  ADD UNIQUE KEY uq_companion_executed_record (executed_module,executed_record_id);

CREATE TABLE companion_execution_receipts (
  proposal_id CHAR(36) PRIMARY KEY,
  account_id CHAR(36) NOT NULL,
  proposal_version INT NOT NULL,
  owner_module VARCHAR(80) NOT NULL,
  record_id CHAR(36) NOT NULL,
  executed_at DATETIME NOT NULL,
  UNIQUE KEY uq_companion_owner_record (owner_module,record_id),
  CONSTRAINT fk_companion_receipt_proposal FOREIGN KEY (proposal_id) REFERENCES companion_proposals(id) ON DELETE CASCADE,
  CONSTRAINT fk_companion_receipt_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;