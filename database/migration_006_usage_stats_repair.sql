USE ihw;

-- Repairs installations where migration_004 was not executed completely.
-- Safe to run repeatedly.
CREATE TABLE IF NOT EXISTS usage_stats (
  asset_no CHAR(7) PRIMARY KEY,
  boot_count INT NULL,
  normal_shutdown_count INT NULL,
  unexpected_shutdown_count INT NULL,
  user_shutdown_count INT NULL,
  last_boot_time DATETIME NULL,
  last_shutdown_time DATETIME NULL,
  completed_session_count INT NULL,
  current_session_hours DECIMAL(12,2) NULL,
  current_session_duration VARCHAR(80) NULL,
  total_usage_hours DECIMAL(14,2) NULL,
  total_usage_duration VARCHAR(100) NULL,
  average_session_hours DECIMAL(12,2) NULL,
  average_session_duration VARCHAR(100) NULL,
  longest_session_hours DECIMAL(12,2) NULL,
  longest_session_duration VARCHAR(100) NULL,
  collected_at DATETIME NULL,
  CONSTRAINT fk_usage_stats_asset_repair FOREIGN KEY (asset_no) REFERENCES assets(asset_no) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO usage_stats (
  asset_no, boot_count, normal_shutdown_count, unexpected_shutdown_count, user_shutdown_count,
  last_boot_time, last_shutdown_time, completed_session_count, current_session_hours, current_session_duration,
  total_usage_hours, total_usage_duration, average_session_hours, average_session_duration,
  longest_session_hours, longest_session_duration, collected_at
)
SELECT asset_no, boot_count, normal_shutdown_count, unexpected_shutdown_count, user_shutdown_count,
  CASE WHEN last_boot_time IS NULL OR last_boot_time='' THEN NULL ELSE STR_TO_DATE(last_boot_time,'%c/%e/%Y %l:%i:%s %p') END,
  CASE WHEN last_shutdown_time IS NULL OR last_shutdown_time='' THEN NULL ELSE STR_TO_DATE(last_shutdown_time,'%c/%e/%Y %l:%i:%s %p') END,
  completed_session_count, current_session_hours, current_session_duration, total_usage_hours, total_usage_duration,
  average_session_hours, average_session_duration, longest_session_hours, longest_session_duration,
  CASE WHEN collected_at IS NULL OR collected_at='' THEN NULL ELSE STR_TO_DATE(collected_at,'%c/%e/%Y %l:%i:%s %p') END
FROM assets
WHERE asset_type='computer'
ON DUPLICATE KEY UPDATE
  boot_count=VALUES(boot_count),normal_shutdown_count=VALUES(normal_shutdown_count),unexpected_shutdown_count=VALUES(unexpected_shutdown_count),user_shutdown_count=VALUES(user_shutdown_count),last_boot_time=VALUES(last_boot_time),last_shutdown_time=VALUES(last_shutdown_time),completed_session_count=VALUES(completed_session_count),current_session_hours=VALUES(current_session_hours),current_session_duration=VALUES(current_session_duration),total_usage_hours=VALUES(total_usage_hours),total_usage_duration=VALUES(total_usage_duration),average_session_hours=VALUES(average_session_hours),average_session_duration=VALUES(average_session_duration),longest_session_hours=VALUES(longest_session_hours),longest_session_duration=VALUES(longest_session_duration),collected_at=VALUES(collected_at);