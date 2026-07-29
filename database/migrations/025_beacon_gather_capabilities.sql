CREATE TABLE IF NOT EXISTS beacon_short_links (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    slug VARCHAR(80) NOT NULL,
    destination_url TEXT NOT NULL,
    label VARCHAR(180) NOT NULL,
    source_domain VARCHAR(40) NULL,
    source_reference VARCHAR(180) NULL,
    status ENUM('active','disabled') NOT NULL DEFAULT 'active',
    visit_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_beacon_slug (slug),
    KEY idx_beacon_owner (account_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS beacon_pages (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    page_key VARCHAR(80) NOT NULL,
    page_type ENUM('link_hub','business_card','wifi','event_landing') NOT NULL,
    title VARCHAR(180) NOT NULL,
    summary TEXT NULL,
    payload_json JSON NOT NULL,
    visibility ENUM('private','unlisted','public') NOT NULL DEFAULT 'unlisted',
    source_domain VARCHAR(40) NULL,
    source_reference VARCHAR(180) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_beacon_page_key (page_key),
    KEY idx_beacon_page_owner (account_id, page_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS beacon_qr_definitions (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    target_type VARCHAR(40) NOT NULL,
    target_reference VARCHAR(180) NOT NULL,
    encoded_value TEXT NOT NULL,
    label VARCHAR(180) NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_beacon_qr_target (account_id, target_type, target_reference),
    KEY idx_beacon_qr_owner (account_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gather_events (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    venue VARCHAR(255) NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NULL,
    timezone VARCHAR(80) NOT NULL DEFAULT 'UTC',
    visibility ENUM('private','unlisted','public') NOT NULL DEFAULT 'unlisted',
    status ENUM('draft','published','cancelled','completed') NOT NULL DEFAULT 'draft',
    capacity INT UNSIGNED NULL,
    beacon_short_link_id CHAR(36) NULL,
    beacon_page_id CHAR(36) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_gather_owner (account_id, starts_at),
    KEY idx_gather_status (status, starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gather_rsvps (
    id CHAR(36) PRIMARY KEY,
    event_id CHAR(36) NOT NULL,
    account_id CHAR(36) NULL,
    guest_name VARCHAR(180) NULL,
    response ENUM('yes','no','maybe','waitlist') NOT NULL,
    party_size INT UNSIGNED NOT NULL DEFAULT 1,
    note VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_gather_rsvp_event (event_id, response)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gather_signup_slots (
    id CHAR(36) PRIMARY KEY,
    event_id CHAR(36) NOT NULL,
    slot_type ENUM('shift','potluck','item','task') NOT NULL,
    title VARCHAR(180) NOT NULL,
    description VARCHAR(500) NULL,
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    quantity_needed INT UNSIGNED NOT NULL DEFAULT 1,
    quantity_claimed INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_gather_slots_event (event_id, slot_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gather_signup_commitments (
    id CHAR(36) PRIMARY KEY,
    slot_id CHAR(36) NOT NULL,
    account_id CHAR(36) NULL,
    participant_name VARCHAR(180) NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('active','cancelled','completed') NOT NULL DEFAULT 'active',
    note VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_gather_commitment_slot (slot_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gather_checkins (
    id CHAR(36) PRIMARY KEY,
    event_id CHAR(36) NOT NULL,
    account_id CHAR(36) NULL,
    attendee_label VARCHAR(180) NULL,
    checked_in_at DATETIME NOT NULL,
    source ENUM('manual','beacon_qr') NOT NULL DEFAULT 'manual',
    KEY idx_gather_checkin_event (event_id, checked_in_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;