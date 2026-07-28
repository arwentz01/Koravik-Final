ALTER TABLE chronicle_entries
  MODIFY entry_type VARCHAR(40) NOT NULL,
  MODIFY status VARCHAR(20) NOT NULL DEFAULT 'active',
  ADD COLUMN provenance_type VARCHAR(40) NOT NULL DEFAULT 'system' AFTER body,
  ADD COLUMN provenance_label VARCHAR(255) NULL AFTER provenance_type,
  ADD COLUMN editable TINYINT(1) NOT NULL DEFAULT 0 AFTER provenance_label,
  ADD COLUMN updated_at DATETIME NULL AFTER created_at,
  ADD COLUMN archived_at DATETIME NULL AFTER reversed_at,
  ADD COLUMN deleted_at DATETIME NULL AFTER archived_at;

CREATE TABLE chronicle_tags (
  entry_id CHAR(36) NOT NULL,
  tag_name VARCHAR(60) NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (entry_id,tag_name),
  CONSTRAINT fk_chronicle_tag_entry FOREIGN KEY (entry_id) REFERENCES chronicle_entries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE chronicle_entries SET provenance_type=CASE WHEN entry_type='reflection' THEN 'player_approved' WHEN entry_type='world' THEN 'world' ELSE 'quest_event' END, provenance_label=CASE WHEN entry_type='reflection' THEN 'Approved reflection' WHEN entry_type='world' THEN 'World-authored moment' ELSE 'Quest completion moment' END, editable=CASE WHEN entry_type='reflection' THEN 1 ELSE 0 END, updated_at=created_at;
