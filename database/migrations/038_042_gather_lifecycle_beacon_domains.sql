CREATE TABLE IF NOT EXISTS beacon_domains (
    id CHAR(36) PRIMARY KEY,
    hostname VARCHAR(253) NOT NULL,
    domain_type ENUM('platform','organization','personal') NOT NULL DEFAULT 'organization',
    owner_type ENUM('platform','organization','account') NOT NULL DEFAULT 'platform',
    owner_id CHAR(36) NULL,
    root_redirect_url VARCHAR(2048) NULL,
    verification_status ENUM('pending','verified','failed','suspended') NOT NULL DEFAULT 'pending',
    verification_token CHAR(64) NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    verified_at DATETIME NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_beacon_domain_hostname (hostname),
    KEY idx_beacon_domain_owner (owner_type,owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO beacon_domains (id,hostname,domain_type,owner_type,owner_id,root_redirect_url,verification_status,is_default,created_at,verified_at,updated_at)
SELECT '00000000-0000-4000-8000-000000000001','krvk.nl','platform','platform',NULL,'https://koravik.com/','verified',1,UTC_TIMESTAMP(),UTC_TIMESTAMP(),UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM beacon_domains WHERE hostname='krvk.nl');

ALTER TABLE beacon_short_links ADD COLUMN domain_id CHAR(36) NULL AFTER account_id;
ALTER TABLE beacon_short_links ADD UNIQUE KEY uq_beacon_domain_slug (domain_id,slug);
ALTER TABLE beacon_pages ADD COLUMN domain_id CHAR(36) NULL AFTER account_id;
ALTER TABLE beacon_pages ADD UNIQUE KEY uq_beacon_page_domain_key (domain_id,page_key);

UPDATE beacon_short_links SET domain_id='00000000-0000-4000-8000-000000000001' WHERE domain_id IS NULL;
UPDATE beacon_pages SET domain_id='00000000-0000-4000-8000-000000000001' WHERE domain_id IS NULL;

CREATE TABLE IF NOT EXISTS gather_agenda_items (
    id CHAR(36) PRIMARY KEY,
    event_id CHAR(36) NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NULL,
    location_label VARCHAR(255) NULL,
    position INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('scheduled','changed','cancelled') NOT NULL DEFAULT 'scheduled',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_gather_agenda_event (event_id,starts_at,position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gather_agenda_favorites (
    id CHAR(36) PRIMARY KEY,
    agenda_item_id CHAR(36) NOT NULL,
    account_id CHAR(36) NULL,
    guest_email VARCHAR(254) NULL,
    reminder_minutes INT UNSIGNED NULL,
    reminder_status ENUM('none','pending','queued','sent','cancelled') NOT NULL DEFAULT 'none',
    reminder_delivery_id CHAR(36) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_gather_favorite_account (agenda_item_id,account_id),
    UNIQUE KEY uq_gather_favorite_guest (agenda_item_id,guest_email),
    KEY idx_gather_favorite_reminder (reminder_status,reminder_minutes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE gather_events ADD COLUMN lifecycle_status ENUM('active','completed','cancelled','archived') NOT NULL DEFAULT 'active' AFTER status;
ALTER TABLE gather_events ADD COLUMN closed_at DATETIME NULL AFTER lifecycle_status;
ALTER TABLE gather_events ADD COLUMN closeout_note TEXT NULL AFTER closed_at;

CREATE TABLE IF NOT EXISTS gather_walkins (
    id CHAR(36) PRIMARY KEY,
    event_id CHAR(36) NOT NULL,
    attendee_name VARCHAR(180) NOT NULL,
    attendee_email VARCHAR(254) NULL,
    party_count INT UNSIGNED NOT NULL DEFAULT 1,
    checked_in_by_account_id CHAR(36) NOT NULL,
    checked_in_at DATETIME NOT NULL,
    note VARCHAR(500) NULL,
    KEY idx_gather_walkin_event (event_id,checked_in_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gather_outcome_proposals (
    id CHAR(36) PRIMARY KEY,
    event_id CHAR(36) NOT NULL,
    account_id CHAR(36) NOT NULL,
    outcome_type ENUM('chronicle_reflection','quest_progress','journey_invitation','world_fact') NOT NULL,
    summary VARCHAR(500) NOT NULL,
    minimized_payload_json JSON NULL,
    status ENUM('proposed','approved','declined','applied','revoked') NOT NULL DEFAULT 'proposed',
    approved_at DATETIME NULL,
    applied_at DATETIME NULL,
    revoked_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_gather_outcome_account (account_id,status,created_at),
    KEY idx_gather_outcome_event (event_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;