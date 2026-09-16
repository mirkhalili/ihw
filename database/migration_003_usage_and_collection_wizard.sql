USE ihw;

ALTER TABLE assets
  ADD COLUMN IF NOT EXISTS boot_count INT NULL,
  ADD COLUMN IF NOT EXISTS normal_shutdown_count INT NULL,
  ADD COLUMN IF NOT EXISTS unexpected_shutdown_count INT NULL,
  ADD COLUMN IF NOT EXISTS user_shutdown_count INT NULL,
  ADD COLUMN IF NOT EXISTS last_boot_time VARCHAR(80) NULL,
  ADD COLUMN IF NOT EXISTS last_shutdown_time VARCHAR(80) NULL,
  ADD COLUMN IF NOT EXISTS completed_session_count INT NULL,
  ADD COLUMN IF NOT EXISTS current_session_hours DECIMAL(12,2) NULL,
  ADD COLUMN IF NOT EXISTS current_session_duration VARCHAR(80) NULL,
  ADD COLUMN IF NOT EXISTS total_usage_hours DECIMAL(14,2) NULL,
  ADD COLUMN IF NOT EXISTS total_usage_duration VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS average_session_hours DECIMAL(12,2) NULL,
  ADD COLUMN IF NOT EXISTS average_session_duration VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS longest_session_hours DECIMAL(12,2) NULL,
  ADD COLUMN IF NOT EXISTS longest_session_duration VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS collected_at VARCHAR(80) NULL;

CREATE INDEX IF NOT EXISTS idx_assets_usage ON assets(asset_type, unexpected_shutdown_count, collected_at);
