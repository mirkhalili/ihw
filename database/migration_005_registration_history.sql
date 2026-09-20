-- Registration history for hardware collection/approval.
-- Version 0.1.0.1: CollectedAt is retained from CSV/usage collection data;
-- registered_at, registered_ip and registered_by record final registration.
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

DROP TRIGGER IF EXISTS trg_assets_registration_history;
DELIMITER $$
CREATE TRIGGER trg_assets_registration_history
AFTER INSERT ON assets
FOR EACH ROW
BEGIN
    INSERT INTO registration_history(asset_no,collected_at,registered_at,registered_ip,registered_by)
    VALUES(NEW.asset_no,NULL,CURRENT_TIMESTAMP,@ihw_client_ip,IFNULL(@ihw_user_id,NEW.created_by));
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_usage_stats_registration_collected_at;
DELIMITER $$
CREATE TRIGGER trg_usage_stats_registration_collected_at
AFTER INSERT ON usage_stats
FOR EACH ROW
BEGIN
    UPDATE registration_history
    SET collected_at=NEW.collected_at
    WHERE asset_no=NEW.asset_no
      AND id=(SELECT rid FROM (SELECT MAX(id) AS rid FROM registration_history WHERE asset_no=NEW.asset_no) x);
END$$
DELIMITER ;
