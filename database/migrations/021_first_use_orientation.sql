CREATE TABLE account_orientation (
  account_id CHAR(36) PRIMARY KEY,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  next_step VARCHAR(40) NULL,
  completed_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  CONSTRAINT fk_account_orientation_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO account_orientation (account_id,status,next_step,completed_at,created_at,updated_at)
SELECT id,'complete','hearth',UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP()
FROM platform_accounts;
