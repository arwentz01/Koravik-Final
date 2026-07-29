CREATE TABLE IF NOT EXISTS gather_announcements (
    id CHAR(36) PRIMARY KEY,
    event_id CHAR(36) NOT NULL,
    author_account_id CHAR(36) NOT NULL,
    audience ENUM('all','confirmed','waitlisted','volunteers','slot') NOT NULL DEFAULT 'all',
    audience_reference CHAR(36) NULL,
    title VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    urgency ENUM('normal','urgent') NOT NULL DEFAULT 'normal',
    email_enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    KEY idx_gather_announcement_event (event_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE gather_checkins ADD COLUMN rsvp_id CHAR(36) NULL AFTER event_id;
ALTER TABLE gather_checkins ADD COLUMN party_count INT UNSIGNED NOT NULL DEFAULT 1 AFTER attendee_label;
ALTER TABLE gather_checkins ADD COLUMN corrected_at DATETIME NULL AFTER checked_in_at;
ALTER TABLE gather_checkins ADD COLUMN corrected_by_account_id CHAR(36) NULL AFTER corrected_at;
ALTER TABLE gather_checkins ADD COLUMN correction_note VARCHAR(500) NULL AFTER corrected_by_account_id;
ALTER TABLE gather_checkins ADD UNIQUE KEY uq_gather_checkin_rsvp (event_id, rsvp_id);

ALTER TABLE platform_mail_deliveries ADD COLUMN event_id CHAR(36) NULL AFTER message_type;
ALTER TABLE platform_mail_deliveries ADD COLUMN resend_of_id CHAR(36) NULL AFTER event_id;
ALTER TABLE platform_mail_deliveries ADD KEY idx_platform_mail_event (event_id, created_at);
