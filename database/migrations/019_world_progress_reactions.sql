ALTER TABLE world_reactions
  ADD COLUMN source_fact_key VARCHAR(120) NULL AFTER source_event_id,
  ADD COLUMN source_fact_summary VARCHAR(500) NULL AFTER source_fact_key,
  ADD COLUMN rule_key VARCHAR(160) NULL AFTER source_fact_summary,
  ADD COLUMN interpreted_at DATETIME NULL AFTER rule_key;

CREATE TABLE world_story_history (
  id CHAR(36) PRIMARY KEY,
  installation_id CHAR(36) NOT NULL,
  history_type VARCHAR(60) NOT NULL,
  history_key VARCHAR(160) NOT NULL,
  title VARCHAR(180) NOT NULL,
  explanation VARCHAR(500) NOT NULL,
  source_reaction_id CHAR(36) NULL,
  occurred_at DATETIME NOT NULL,
  UNIQUE KEY uq_world_story_history (installation_id,history_type,history_key),
  INDEX idx_world_story_history_time (installation_id,occurred_at),
  CONSTRAINT fk_story_history_installation FOREIGN KEY (installation_id) REFERENCES world_installations(id) ON DELETE CASCADE,
  CONSTRAINT fk_story_history_reaction FOREIGN KEY (source_reaction_id) REFERENCES world_reactions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO world_story_history (id,installation_id,history_type,history_key,title,explanation,occurred_at)
SELECT UUID(),installation_id,'choice',CONCAT(scene_key,':',choice_key),choice_label,'A narrative choice became part of this World.',created_at
FROM world_choice_history
ON DUPLICATE KEY UPDATE title=VALUES(title);

INSERT INTO world_story_history (id,installation_id,history_type,history_key,title,explanation,occurred_at)
SELECT UUID(),installation_id,'keepsake',keepsake_key,name,description,acquired_at
FROM world_keepsakes
ON DUPLICATE KEY UPDATE title=VALUES(title),explanation=VALUES(explanation);

INSERT INTO world_story_history (id,installation_id,history_type,history_key,title,explanation,source_reaction_id,occurred_at)
SELECT UUID(),installation_id,'reaction',id,title,explanation,id,created_at
FROM world_reactions
ON DUPLICATE KEY UPDATE title=VALUES(title),explanation=VALUES(explanation);