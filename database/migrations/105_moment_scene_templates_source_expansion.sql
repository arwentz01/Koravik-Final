ALTER TABLE platform_moments
    ADD COLUMN scene_template ENUM('caretaker','room','silent','memory','companion') NOT NULL DEFAULT 'room' AFTER moment_key,
    ADD COLUMN speaker_label VARCHAR(120) NULL AFTER scene_template,
    ADD COLUMN primary_object VARCHAR(180) NULL AFTER speaker_label,
    ADD COLUMN ambient_detail VARCHAR(255) NULL AFTER primary_object,
    ADD COLUMN recommended_action_label VARCHAR(120) NULL AFTER ambient_detail;

UPDATE platform_moments
SET scene_template = CASE
        WHEN source_type = 'relationship_conversation' THEN 'caretaker'
        WHEN source_type IN ('world_reaction','garden_tending','world_choice') THEN 'room'
        WHEN source_type IN ('epic_reclamation') THEN 'memory'
        ELSE 'room'
    END,
    speaker_label = CASE WHEN source_type = 'relationship_conversation' THEN 'The Caretaker' ELSE speaker_label END,
    primary_object = CASE
        WHEN room_key = 'fireplace' THEN 'fireplace'
        WHEN room_key = 'garden' THEN 'garden window'
        WHEN room_key = 'workshop' THEN 'unfinished shelf'
        WHEN room_key = 'library' THEN 'open book'
        ELSE primary_object
    END,
    ambient_detail = COALESCE(ambient_detail, 'A source-owned change became visible without becoming a task or score.'),
    recommended_action_label = COALESCE(recommended_action_label, 'Continue gently');
