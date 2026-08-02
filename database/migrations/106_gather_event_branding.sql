ALTER TABLE gather_events
    ADD COLUMN event_accent_color CHAR(7) NULL AFTER organizer_reply_to_email,
    ADD COLUMN event_header_style ENUM('classic','forest','gold','navy','custom') NOT NULL DEFAULT 'classic' AFTER event_accent_color;
