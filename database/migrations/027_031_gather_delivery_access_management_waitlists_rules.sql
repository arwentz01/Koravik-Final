CREATE TABLE IF NOT EXISTS platform_mail_deliveries (
    id CHAR(36) PRIMARY KEY,
    message_type VARCHAR(80) NOT NULL,
    recipient_email VARCHAR(254) NOT NULL,
    recipient_name VARCHAR(180) NULL,
    reply_to_email VARCHAR(254) NULL,
    reply_to_name VARCHAR(180) NULL,
    subject VARCHAR(255) NOT NULL,
    html_body MEDIUMTEXT NOT NULL,
    text_body MEDIUMTEXT NOT NULL,
    status ENUM('pending','processing','sent','retry','failed') NOT NULL DEFAULT 'pending',
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    available_at DATETIME NOT NULL,
    claimed_at DATETIME NULL,
    sent_at DATETIME NULL,
    provider_reference VARCHAR(255) NULL,
    failure_reason VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_platform_mail_queue (status, available_at),
    KEY idx_platform_mail_recipient (recipient_email, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gather_event_access_grants (
    id CHAR(36) PRIMARY KEY,
    event_id CHAR(36) NOT NULL,
    grant_type ENUM('email','account','friend','organization','household') NOT NULL,
    grant_reference VARCHAR(254) NOT NULL,
    created_by_account_id CHAR(36) NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_gather_access_grant (event_id, grant_type, grant_reference),
    KEY idx_gather_access_event (event_id, grant_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE gather_rsvps ADD COLUMN status ENUM('active','cancelled') NOT NULL DEFAULT 'active' AFTER response;
ALTER TABLE gather_rsvps ADD COLUMN management_token_expires_at DATETIME NULL AFTER management_token_created_at;
ALTER TABLE gather_rsvps ADD COLUMN waitlist_offer_expires_at DATETIME NULL AFTER promoted_at;
ALTER TABLE gather_rsvps ADD COLUMN waitlist_offer_status ENUM('none','offered','accepted','expired','declined') NOT NULL DEFAULT 'none' AFTER waitlist_offer_expires_at;

ALTER TABLE gather_signup_commitments ADD COLUMN management_token_hash CHAR(64) NULL AFTER participant_email;
ALTER TABLE gather_signup_commitments ADD COLUMN waitlist_offer_expires_at DATETIME NULL AFTER promoted_at;
ALTER TABLE gather_signup_commitments ADD COLUMN waitlist_offer_status ENUM('none','offered','accepted','expired','declined') NOT NULL DEFAULT 'none' AFTER waitlist_offer_expires_at;

ALTER TABLE gather_signup_slots ADD COLUMN category_key VARCHAR(80) NULL AFTER slot_type;
ALTER TABLE gather_signup_slots ADD COLUMN max_quantity_per_commitment INT UNSIGNED NULL AFTER max_signups_per_participant;
ALTER TABLE gather_signup_slots ADD COLUMN require_attending_rsvp TINYINT(1) NOT NULL DEFAULT 1 AFTER overlapping_shifts_allowed;

ALTER TABLE gather_events ADD COLUMN max_signups_per_participant INT UNSIGNED NULL AFTER restricted_reference;
ALTER TABLE gather_events ADD COLUMN waitlist_offer_minutes INT UNSIGNED NOT NULL DEFAULT 1440 AFTER max_signups_per_participant;
ALTER TABLE gather_events ADD COLUMN organizer_reply_to_email VARCHAR(254) NULL AFTER waitlist_offer_minutes;
