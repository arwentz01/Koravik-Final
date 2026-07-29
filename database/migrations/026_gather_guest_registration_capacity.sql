ALTER TABLE gather_events
    MODIFY visibility ENUM('restricted','unlisted','public') NOT NULL DEFAULT 'unlisted',
    ADD COLUMN guest_registration_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER capacity,
    ADD COLUMN additional_guests_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER guest_registration_enabled,
    ADD COLUMN max_additional_guests INT UNSIGNED NOT NULL DEFAULT 0 AFTER additional_guests_enabled,
    ADD COLUMN waitlist_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER max_additional_guests,
    ADD COLUMN automatic_waitlist_promotion TINYINT(1) NOT NULL DEFAULT 0 AFTER waitlist_enabled,
    ADD COLUMN restricted_scope ENUM('invited','friends','organization','household') NULL AFTER automatic_waitlist_promotion,
    ADD COLUMN restricted_reference CHAR(36) NULL AFTER restricted_scope;

ALTER TABLE gather_rsvps
    ADD COLUMN guest_email VARCHAR(254) NULL AFTER guest_name,
    ADD COLUMN management_token_hash CHAR(64) NULL AFTER note,
    ADD COLUMN management_token_created_at DATETIME NULL AFTER management_token_hash,
    ADD COLUMN management_token_revoked_at DATETIME NULL AFTER management_token_created_at,
    ADD COLUMN waitlist_position INT UNSIGNED NULL AFTER management_token_revoked_at,
    ADD COLUMN promoted_at DATETIME NULL AFTER waitlist_position,
    ADD UNIQUE KEY uq_gather_rsvp_event_email (event_id, guest_email),
    ADD KEY idx_gather_rsvp_lookup (guest_email, response),
    ADD KEY idx_gather_rsvp_token (management_token_hash);

CREATE TABLE IF NOT EXISTS gather_rsvp_guests (
    id CHAR(36) PRIMARY KEY,
    rsvp_id CHAR(36) NOT NULL,
    guest_name VARCHAR(180) NULL,
    guest_email VARCHAR(254) NULL,
    guest_type ENUM('adult','child','unspecified') NOT NULL DEFAULT 'unspecified',
    created_at DATETIME NOT NULL,
    KEY idx_gather_rsvp_guests (rsvp_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE gather_signup_slots
    ADD COLUMN multiple_signups_allowed TINYINT(1) NOT NULL DEFAULT 1 AFTER quantity_claimed,
    ADD COLUMN max_signups_per_participant INT UNSIGNED NULL AFTER multiple_signups_allowed,
    ADD COLUMN waitlist_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER max_signups_per_participant,
    ADD COLUMN overlapping_shifts_allowed TINYINT(1) NOT NULL DEFAULT 0 AFTER waitlist_enabled;

ALTER TABLE gather_signup_commitments
    MODIFY status ENUM('active','waitlist','cancelled','completed') NOT NULL DEFAULT 'active',
    ADD COLUMN rsvp_id CHAR(36) NULL AFTER account_id,
    ADD COLUMN participant_email VARCHAR(254) NULL AFTER participant_name,
    ADD COLUMN waitlist_position INT UNSIGNED NULL AFTER note,
    ADD COLUMN promoted_at DATETIME NULL AFTER waitlist_position,
    ADD KEY idx_gather_commitment_participant (slot_id, participant_email, status),
    ADD KEY idx_gather_commitment_rsvp (rsvp_id, status);

CREATE TABLE IF NOT EXISTS gather_management_link_deliveries (
    id CHAR(36) PRIMARY KEY,
    email VARCHAR(254) NOT NULL,
    rsvp_id CHAR(36) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    delivery_status ENUM('pending','sent','failed','cancelled') NOT NULL DEFAULT 'pending',
    requested_at DATETIME NOT NULL,
    sent_at DATETIME NULL,
    failure_reason VARCHAR(500) NULL,
    KEY idx_gather_delivery_queue (delivery_status, requested_at),
    KEY idx_gather_delivery_email (email, requested_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;