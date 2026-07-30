ALTER TABLE account_settings
    ADD COLUMN text_scale ENUM('standard','large','larger') NOT NULL DEFAULT 'standard' AFTER high_contrast,
    ADD COLUMN typeface ENUM('system','readable') NOT NULL DEFAULT 'system' AFTER text_scale,
    ADD COLUMN content_spacing ENUM('standard','relaxed') NOT NULL DEFAULT 'standard' AFTER typeface,
    ADD COLUMN emphasize_links TINYINT(1) NOT NULL DEFAULT 0 AFTER content_spacing,
    ADD COLUMN enhanced_focus TINYINT(1) NOT NULL DEFAULT 0 AFTER emphasize_links,
    ADD COLUMN reading_width ENUM('standard','narrow') NOT NULL DEFAULT 'standard' AFTER enhanced_focus;
