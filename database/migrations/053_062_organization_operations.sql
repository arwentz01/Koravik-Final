ALTER TABLE organizations
    ADD COLUMN public_name VARCHAR(180) NULL AFTER name,
    ADD COLUMN contact_email VARCHAR(254) NULL AFTER summary,
    ADD COLUMN brand_color CHAR(7) NULL AFTER contact_email,
    ADD COLUMN beacon_domain_id CHAR(36) NULL AFTER brand_color,
    ADD COLUMN settings_json JSON NULL AFTER primary_timezone;

ALTER TABLE organization_invitations
    ADD COLUMN delivery_id CHAR(36) NULL AFTER invited_by_account_id,
    ADD COLUMN last_sent_at DATETIME NULL AFTER delivery_id,
    ADD COLUMN revoked_at DATETIME NULL AFTER accepted_by_account_id;

CREATE TABLE IF NOT EXISTS organization_teams (
    id CHAR(36) PRIMARY KEY,
    organization_id CHAR(36) NOT NULL,
    name VARCHAR(180) NOT NULL,
    summary TEXT NULL,
    status ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_by_account_id CHAR(36) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_organization_team (organization_id,status,name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS organization_team_memberships (
    id CHAR(36) PRIMARY KEY,
    team_id CHAR(36) NOT NULL,
    membership_id CHAR(36) NOT NULL,
    team_role ENUM('lead','member') NOT NULL DEFAULT 'member',
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_organization_team_member (team_id,membership_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS organization_quest_proposals (
    id CHAR(36) PRIMARY KEY,
    organization_id CHAR(36) NOT NULL,
    proposed_by_account_id CHAR(36) NOT NULL,
    recipient_account_id CHAR(36) NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    status ENUM('proposed','accepted','declined','revoked') NOT NULL DEFAULT 'proposed',
    quest_id CHAR(36) NULL,
    responded_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_organization_quest_recipient (recipient_account_id,status,created_at),
    KEY idx_organization_quest_org (organization_id,status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS organization_recovery_records (
    id CHAR(36) PRIMARY KEY,
    organization_id CHAR(36) NOT NULL,
    actor_account_id CHAR(36) NOT NULL,
    action VARCHAR(100) NOT NULL,
    previous_status VARCHAR(32) NULL,
    new_status VARCHAR(32) NULL,
    created_at DATETIME NOT NULL,
    KEY idx_organization_recovery (organization_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
