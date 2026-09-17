USE ihw;

-- CollectedAt remains the source collection timestamp from HWiNFO.
-- audit_logs stores the actual registration user, IP and server timestamp.
ALTER TABLE assets
  ADD COLUMN IF NOT EXISTS registration_ip VARCHAR(45) NULL,
  ADD COLUMN IF NOT EXISTS registration_at DATETIME NULL;

CREATE INDEX IF NOT EXISTS idx_assets_registration_at ON assets(registration_at);
