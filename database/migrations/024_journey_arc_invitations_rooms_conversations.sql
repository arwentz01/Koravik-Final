CREATE TABLE story_invitations (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    world_key VARCHAR(100) NOT NULL,
    invitation_key VARCHAR(120) NOT NULL,
    title VARCHAR(180) NOT NULL,
    body TEXT NOT NULL,
    suggested_quest_title VARCHAR(180) NOT NULL,
    suggested_purpose TEXT NULL,
    suggested_next_step VARCHAR(180) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'open',
    snoozed_until DATETIME NULL,
    accepted_quest_id CHAR(36) NULL,
    created_at DATETIME NOT NULL,
    decided_at DATETIME NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_story_invitation (account_id, world_key, invitation_key),
    INDEX idx_story_invitation_status (account_id, status, snoozed_until),
    CONSTRAINT fk_story_invitation_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE,
    CONSTRAINT fk_story_invitation_quest FOREIGN KEY (accepted_quest_id) REFERENCES quests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE healing_home_keepsake_placements (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    keepsake_key VARCHAR(140) NOT NULL,
    room_key VARCHAR(80) NOT NULL,
    label VARCHAR(180) NOT NULL,
    description TEXT NULL,
    source_type VARCHAR(40) NOT NULL,
    source_id CHAR(36) NULL,
    placed_at DATETIME NOT NULL,
    UNIQUE KEY uq_home_keepsake_source (account_id, source_type, source_id),
    INDEX idx_home_keepsake_room (account_id, room_key, placed_at),
    CONSTRAINT fk_home_keepsake_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE relationship_conversations (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    character_key VARCHAR(100) NOT NULL,
    conversation_type VARCHAR(40) NOT NULL,
    prompt_key VARCHAR(120) NOT NULL,
    player_choice VARCHAR(80) NOT NULL,
    character_response TEXT NOT NULL,
    remembered_context TEXT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_relationship_conversation (account_id, character_key, created_at),
    CONSTRAINT fk_relationship_conversation_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE quest_source_proposals (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    source_domain VARCHAR(40) NOT NULL,
    source_reference VARCHAR(180) NOT NULL,
    title VARCHAR(180) NOT NULL,
    purpose TEXT NULL,
    next_step VARCHAR(180) NULL,
    proposal_kind VARCHAR(60) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'open',
    created_quest_id CHAR(36) NULL,
    created_at DATETIME NOT NULL,
    decided_at DATETIME NULL,
    UNIQUE KEY uq_quest_source_proposal (account_id, source_domain, source_reference, proposal_kind),
    INDEX idx_quest_source_proposal_status (account_id, status, created_at),
    CONSTRAINT fk_quest_source_proposal_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE,
    CONSTRAINT fk_quest_source_proposal_quest FOREIGN KEY (created_quest_id) REFERENCES quests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cooperative_quest_invitations (
    id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    quest_id CHAR(36) NOT NULL,
    invited_by_label VARCHAR(180) NOT NULL,
    collaboration_context VARCHAR(40) NOT NULL DEFAULT 'independent',
    context_reference VARCHAR(180) NULL,
    contribution_label VARCHAR(180) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'open',
    created_at DATETIME NOT NULL,
    decided_at DATETIME NULL,
    INDEX idx_cooperative_invitation (account_id, status, created_at),
    CONSTRAINT fk_cooperative_invitation_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE,
    CONSTRAINT fk_cooperative_invitation_quest FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;