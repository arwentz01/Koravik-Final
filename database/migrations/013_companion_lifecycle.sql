ALTER TABLE companion_proposals
  ADD COLUMN clarification_text TEXT NULL AFTER consequence,
  ADD COLUMN failure_code VARCHAR(50) NULL AFTER clarification_text,
  ADD COLUMN failure_message TEXT NULL AFTER failure_code,
  ADD COLUMN execution_attempts INT NOT NULL DEFAULT 0 AFTER failure_message,
  ADD COLUMN last_attempt_at DATETIME NULL AFTER execution_attempts,
  ADD COLUMN renewed_from CHAR(36) NULL AFTER last_attempt_at;

CREATE INDEX idx_companion_expiration ON companion_proposals (status, expires_at);
