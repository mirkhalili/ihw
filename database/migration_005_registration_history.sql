-- Registration history for hardware collection/approval.
-- CollectedAt remains the source collection timestamp; this table records when,
-- from which IP, and by which authenticated user the final registration happened.
CREATE TABLE IF NOT EXISTS registration_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    asset_no CHAR(7) NOT NULL,
    collected_at DATETIME NULL,
    registered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    registered_ip VARCHAR(45) NULL,
    registered_by BIGINT UNSIGNED NULL,
    PRIMARY KEY (id),
    KEY idx_registration_history_asset (asset_no),
    KEY idx_registration_history_registered_at (registered_at),
    CONSTRAINT fk_registration_history_asset FOREIGN KEY (asset_no) REFERENCES assets(asset_no) ON DELETE CASCADE,
    CONSTRAINT fk_registration_history_user FOREIGN KEY (registered_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
