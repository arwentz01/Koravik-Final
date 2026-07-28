CREATE TABLE world_objectives (
  id CHAR(36) PRIMARY KEY,
  installation_id CHAR(36) NOT NULL,
  objective_key VARCHAR(100) NOT NULL,
  title VARCHAR(180) NOT NULL,
  description VARCHAR(500) NOT NULL,
  status ENUM('active','completed','retired') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL,
  completed_at DATETIME NULL,
  UNIQUE KEY uq_world_objective (installation_id,objective_key),
  INDEX idx_world_objectives_status (installation_id,status),
  CONSTRAINT fk_world_objective_installation FOREIGN KEY (installation_id) REFERENCES world_installations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE world_keepsakes (
  id CHAR(36) PRIMARY KEY,
  installation_id CHAR(36) NOT NULL,
  keepsake_key VARCHAR(100) NOT NULL,
  name VARCHAR(180) NOT NULL,
  description VARCHAR(500) NOT NULL,
  source_scene VARCHAR(100) NOT NULL,
  acquired_at DATETIME NOT NULL,
  UNIQUE KEY uq_world_keepsake (installation_id,keepsake_key),
  CONSTRAINT fk_world_keepsake_installation FOREIGN KEY (installation_id) REFERENCES world_installations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO world_objectives (id,installation_id,objective_key,title,description,status,created_at)
SELECT UUID(),wi.id,'choose-refuge','Decide what kind of refuge this will become','The Caretaker has opened the eastern room. Choose what the restored space should offer.','active',UTC_TIMESTAMP()
FROM world_installations wi
JOIN world_choice_history wc ON wc.installation_id=wi.id AND wc.scene_key='caretaker-welcome'
WHERE wi.world_key='epic-ordinary'
ON DUPLICATE KEY UPDATE title=VALUES(title);