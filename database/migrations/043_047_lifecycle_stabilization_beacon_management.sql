ALTER TABLE gather_agenda_favorites
    ADD COLUMN unsubscribe_token_hash CHAR(64) NULL AFTER reminder_delivery_id,
    ADD COLUMN cancelled_at DATETIME NULL AFTER unsubscribe_token_hash,
    ADD UNIQUE KEY uq_gather_favorite_unsubscribe (unsubscribe_token_hash);

ALTER TABLE gather_outcome_proposals
    ADD COLUMN application_status ENUM('not_ready','pending','applied','failed','revoked') NOT NULL DEFAULT 'not_ready' AFTER applied_at,
    ADD COLUMN application_reference VARCHAR(255) NULL AFTER application_status,
    ADD COLUMN application_error VARCHAR(500) NULL AFTER application_reference;

CREATE TABLE IF NOT EXISTS gather_outcome_applications (
    id CHAR(36) PRIMARY KEY,
    proposal_id CHAR(36) NOT NULL,
    destination_type ENUM('chronicle','quest','journey','world') NOT NULL,
    destination_reference VARCHAR(255) NULL,
    status ENUM('pending','applied','failed','revoked') NOT NULL DEFAULT 'pending',
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    last_error VARCHAR(500) NULL,
    applied_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_gather_outcome_application (proposal_id),
    KEY idx_gather_outcome_application_status (status,updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE beacon_domains
    ADD COLUMN suspended_at DATETIME NULL AFTER verified_at;

ALTER TABLE beacon_short_links DROP INDEX uq_beacon_slug;
ALTER TABLE beacon_short_links MODIFY COLUMN status ENUM('active','disabled','paused','archived') NOT NULL DEFAULT 'active';
ALTER TABLE beacon_short_links
    ADD COLUMN preferred_domain_id CHAR(36) NULL AFTER domain_id,
    ADD COLUMN archived_at DATETIME NULL AFTER status,
    ADD COLUMN last_destination_check_at DATETIME NULL AFTER archived_at,
    ADD COLUMN destination_health ENUM('unknown','healthy','warning','failed') NOT NULL DEFAULT 'unknown' AFTER last_destination_check_at,
    ADD KEY idx_beacon_link_status (status,updated_at);

CREATE TABLE IF NOT EXISTS beacon_link_revisions (
    id CHAR(36) PRIMARY KEY,
    link_id CHAR(36) NOT NULL,
    changed_by_account_id CHAR(36) NOT NULL,
    action VARCHAR(80) NOT NULL,
    before_json JSON NULL,
    after_json JSON NULL,
    created_at DATETIME NOT NULL,
    KEY idx_beacon_link_revision (link_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS beacon_domain_revisions (
    id CHAR(36) PRIMARY KEY,
    domain_id CHAR(36) NOT NULL,
    changed_by_account_id CHAR(36) NOT NULL,
    action VARCHAR(80) NOT NULL,
    context_json JSON NULL,
    created_at DATETIME NOT NULL,
    KEY idx_beacon_domain_revision (domain_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;