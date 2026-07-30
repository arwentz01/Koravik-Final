CREATE TABLE world_reaction_reviews (
    reaction_id CHAR(36) PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    reviewed_at DATETIME NOT NULL,
    INDEX idx_world_reaction_reviews_account_time (account_id, reviewed_at),
    CONSTRAINT fk_world_reaction_reviews_reaction FOREIGN KEY (reaction_id) REFERENCES world_reactions(id) ON DELETE CASCADE,
    CONSTRAINT fk_world_reaction_reviews_account FOREIGN KEY (account_id) REFERENCES platform_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
