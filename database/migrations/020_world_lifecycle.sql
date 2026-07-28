ALTER TABLE world_installations
  ADD COLUMN installed_version VARCHAR(40) NOT NULL DEFAULT '1.0.0' AFTER world_key,
  ADD COLUMN available_version VARCHAR(40) NOT NULL DEFAULT '1.0.0' AFTER installed_version,
  ADD COLUMN last_played_at DATETIME NULL AFTER installed_at,
  ADD COLUMN state_retained TINYINT(1) NOT NULL DEFAULT 1 AFTER last_played_at,
  ADD COLUMN lifecycle_revision INT NOT NULL DEFAULT 1 AFTER state_retained;

UPDATE world_installations wi
JOIN world_catalog wc ON wc.world_key=wi.world_key
SET wi.available_version=wc.package_version,
    wi.installed_version=COALESCE(NULLIF(wi.installed_version,''),wc.package_version);

CREATE TABLE world_lifecycle_history (
  id CHAR(36) PRIMARY KEY,
  installation_id CHAR(36) NOT NULL,
  action_key VARCHAR(80) NOT NULL,
  prior_status VARCHAR(20) NULL,
  resulting_status VARCHAR(20) NOT NULL,
  consequence_summary VARCHAR(500) NOT NULL,
  revision INT NOT NULL,
  occurred_at DATETIME NOT NULL,
  UNIQUE KEY uq_world_lifecycle_revision (installation_id,revision),
  INDEX idx_world_lifecycle_time (installation_id,occurred_at),
  CONSTRAINT fk_world_lifecycle_installation FOREIGN KEY (installation_id) REFERENCES world_installations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;