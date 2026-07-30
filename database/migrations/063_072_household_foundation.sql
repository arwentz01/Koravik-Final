CREATE TABLE IF NOT EXISTS households (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    summary TEXT NULL,
    primary_timezone VARCHAR(80) NOT NULL DEFAULT 'UTC',
    status ENUM('active','suspended','archived') NOT NULL DEFAULT 'active',
    preferences_json JSON NULL,
    created_by_account_id CHAR(36) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    suspended_at DATETIME NULL,
    archived_at DATETIME NULL,
    KEY idx_household_status (status,updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS household_memberships (
    id CHAR(36) PRIMARY KEY,
    household_id CHAR(36) NOT NULL,
    account_id CHAR(36) NOT NULL,
    role ENUM('owner','admin','member') NOT NULL DEFAULT 'member',
    status ENUM('active','suspended','left','removed') NOT NULL DEFAULT 'active',
    invited_by_account_id CHAR(36) NULL,
    joined_at DATETIME NOT NULL,
    ended_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_household_member (household_id,account_id),
    KEY idx_household_membership_account (account_id,status),
    KEY idx_household_membership_scope (household_id,status,role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS household_invitations (
    id CHAR(36) PRIMARY KEY,
    household_id CHAR(36) NOT NULL,
    email VARCHAR(254) NOT NULL,
    role ENUM('admin','member') NOT NULL DEFAULT 'member',
    token_hash CHAR(64) NOT NULL,
    status ENUM('pending','accepted','expired','revoked') NOT NULL DEFAULT 'pending',
    invited_by_account_id CHAR(36) NOT NULL,
    delivery_id CHAR(36) NULL,
    expires_at DATETIME NOT NULL,
    accepted_by_account_id CHAR(36) NULL,
    last_sent_at DATETIME NULL,
    revoked_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_household_invitation_token (token_hash),
    KEY idx_household_invitation_email (email,status,expires_at),
    KEY idx_household_invitation_scope (household_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS household_resources (
    id CHAR(36) PRIMARY KEY,
    household_id CHAR(36) NOT NULL,
    created_by_account_id CHAR(36) NOT NULL,
    resource_type ENUM('instruction','contact','link','reference') NOT NULL DEFAULT 'reference',
    title VARCHAR(180) NOT NULL,
    body TEXT NULL,
    url VARCHAR(2048) NULL,
    status ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_household_resource (household_id,status,updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS household_quest_proposals (
    id CHAR(36) PRIMARY KEY,
    household_id CHAR(36) NOT NULL,
    proposed_by_account_id CHAR(36) NOT NULL,
    recipient_account_id CHAR(36) NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    recurrence_json JSON NULL,
    status ENUM('proposed','accepted','declined','revoked') NOT NULL DEFAULT 'proposed',
    quest_id CHAR(36) NULL,
    responded_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_household_quest_recipient (recipient_account_id,status,created_at),
    KEY idx_household_quest_scope (household_id,status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS household_activity (
    id CHAR(36) PRIMARY KEY,
    household_id CHAR(36) NOT NULL,
    actor_account_id CHAR(36) NOT NULL,
    action VARCHAR(100) NOT NULL,
    subject_type VARCHAR(80) NOT NULL,
    subject_id CHAR(36) NULL,
    context_json JSON NULL,
    created_at DATETIME NOT NULL,
    KEY idx_household_activity (household_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS household_recovery_records (
    id CHAR(36) PRIMARY KEY,
    household_id CHAR(36) NOT NULL,
    actor_account_id CHAR(36) NOT NULL,
    previous_status VARCHAR(32) NOT NULL,
    new_status VARCHAR(32) NOT NULL,
    created_at DATETIME NOT NULL,
    KEY idx_household_recovery (household_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE gather_events
    MODIFY COLUMN owner_type ENUM('account','organization','household') NOT NULL DEFAULT 'account',
    ADD COLUMN household_id CHAR(36) NULL AFTER organization_id,
    ADD KEY idx_gather_household (household_id,starts_at);
