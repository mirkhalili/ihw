USE ihw;

-- Run this once when upgrading an existing installation created from an older schema.
ALTER TABLE personnel ADD INDEX idx_personnel_national_id (national_id);
ALTER TABLE assets ADD INDEX idx_assets_status (status), ADD INDEX idx_assets_updated (updated_at);
ALTER TABLE audit_logs ADD INDEX idx_audit_user (user_id);
