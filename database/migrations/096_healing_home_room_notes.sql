ALTER TABLE healing_home_rooms
    ADD COLUMN note_text TEXT NULL AFTER state,
    ADD COLUMN note_updated_at DATETIME NULL AFTER note_text;
