ALTER TABLE platform_mail_deliveries MODIFY COLUMN status ENUM('pending','processing','sent','retry','failed','cancelled') NOT NULL DEFAULT 'pending';
ALTER TABLE platform_mail_deliveries ADD COLUMN cancelled_at DATETIME NULL AFTER sent_at;
ALTER TABLE platform_mail_deliveries ADD COLUMN cancelled_by_account_id CHAR(36) NULL AFTER cancelled_at;
ALTER TABLE platform_mail_deliveries ADD COLUMN recovered_at DATETIME NULL AFTER cancelled_by_account_id;
ALTER TABLE platform_mail_deliveries ADD KEY idx_platform_mail_stale (status, claimed_at);
