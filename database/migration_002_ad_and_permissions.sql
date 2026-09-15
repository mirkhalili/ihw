USE ihw;

CREATE TABLE IF NOT EXISTS ad_settings (
 id TINYINT UNSIGNED PRIMARY KEY,
 host VARCHAR(255) NOT NULL DEFAULT '',
 port INT UNSIGNED NOT NULL DEFAULT 389,
 use_tls TINYINT(1) NOT NULL DEFAULT 0,
 base_dn VARCHAR(500) NOT NULL DEFAULT '',
 bind_dn VARCHAR(500) NOT NULL DEFAULT '',
 bind_password_enc TEXT NULL,
 user_filter VARCHAR(1000) NOT NULL DEFAULT '(&(objectCategory=person)(objectClass=user))',
 updated_by BIGINT UNSIGNED NULL,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_asset_permissions (
 user_id BIGINT UNSIGNED NOT NULL,
 asset_type ENUM('computer','printer','display','scanner') NOT NULL,
 can_create TINYINT(1) NOT NULL DEFAULT 1,
 can_edit TINYINT(1) NOT NULL DEFAULT 1,
 PRIMARY KEY(user_id,asset_type),
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
