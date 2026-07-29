CREATE TABLE IF NOT EXISTS organizations (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    summary TEXT NULL,
    primary_timezone VARCHAR(80) NOT NULL DEFAULT 'UTC',
    status ENUM('active','suspended','archived') NOT NULL DEFAULT 'active',
    created_by_account_id CHAR(36) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    suspended_at DATETIME NULL,
    archived_at DATETIME NULL,
    KEY idx_organization_status (status,updated_at),
    KEY idx_organization_creator (created_by_account_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS organization_memberships (
    id CHAR(36) PRIMARY KEY,
    organization_id CHAR(36) NOT NULL,
    account_id CHAR(36) NOT NULL,
    role ENUM('owner','admin','creator','member') NOT NULL DEFAULT 'member',
    status ENUM('active','suspended','left','removed') NOT NULL DEFAULT 'active',
    invited_by_account_id CHAR(36) NULL,
    joined_at DATETIME NOT NULL,
    suspended_at DATETIME NULL,
    ended_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_organization_member (organization_id,account_id),
    KEY idx_organization_membership_account (account_id,status),
    KEY idx_organization_membership_org (organization_id,status,role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS organization_invitations (
    id CHAR(36) PRIMARY KEY,
    organization_id CHAR(36) NOT NULL,
    email VARCHAR(254) NOT NULL,
    role ENUM('admin','creator','member') NOT NULL DEFAULT 'member',
    token_hash CHAR(64) NOT NULL,
    status ENUM('pending','accepted','declined','expired','revoked') NOT NULL DEFAULT 'pending',
    invited_by_account_id CHAR(36) NOT NULL,
    expires_at DATETIME NOT NULL,
    accepted_by_account_id CHAR(36) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_organization_invitation_token (token_hash),
    KEY idx_organization_invitation_email (email,status,expires_at),
    KEY idx_organization_invitation_org (organization_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS organization_activity (
    id CHAR(36) PRIMARY KEY,
    organization_id CHAR(36) NOT NULL,
    actor_account_id CHAR(36) NOT NULL,
    action VARCHAR(100) NOT NULL,
    subject_type VARCHAR(80) NOT NULL,
    subject_id CHAR(36) NULL,
    context_json JSON NULL,
    created_at DATETIME NOT NULL,
    KEY idx_organization_activity (organization_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE gather_events
    ADD COLUMN owner_type ENUM('account','organization') NOT NULL DEFAULT 'account' AFTER account_id,
    ADD COLUMN organization_id CHAR(36) NULL AFTER owner_type,
    ADD KEY idx_gather_organization (organization_id,starts_at);

ALTER TABLE beacon_short_links
    ADD COLUMN owner_type ENUM('account','organization') NOT NULL DEFAULT 'account' AFTER account_id,
    ADD COLUMN organization_id CHAR(36) NULL AFTER owner_type,
    ADD KEY idx_beacon_link_organization (organization_id,created_at);

ALTER TABLE beacon_pages
    ADD COLUMN owner_type ENUM('account','organization') NOT NULL DEFAULT 'account' AFTER account_id,
    ADD COLUMN organization_id CHAR(36) NULL AFTER owner_type,
    ADD KEY idx_beacon_page_organization (organization_id,created_at);

ALTER TABLE beacon_qr_definitions
    ADD COLUMN owner_type ENUM('account','organization') NOT NULL DEFAULT 'account' AFTER account_id,
    ADD COLUMN organization_id CHAR(36) NULL AFTER owner_type,
    ADD KEY idx_beacon_qr_organization (organization_id,created_at);

ALTER TABLE beacon_domains
    ADD COLUMN organization_id CHAR(36) NULL AFTER owner_id,
    ADD KEY idx_beacon_domain_organization (organization_id,verification_status);
