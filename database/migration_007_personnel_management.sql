USE ihw;

ALTER TABLE personnel
  ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) NULL AFTER id,
  ADD COLUMN IF NOT EXISTS last_name VARCHAR(120) NULL AFTER first_name,
  ADD COLUMN IF NOT EXISTS mobile VARCHAR(30) NULL AFTER national_id;

-- National ID is the business key for file-based personnel management.
-- Existing NULL values remain allowed for Active Directory records that do not expose national ID.
SET @has_unique := (
  SELECT COUNT(*) FROM information_schema.statistics
  WHERE table_schema=DATABASE() AND table_name='personnel'
    AND index_name='uq_personnel_national_id'
);
SET @sql := IF(@has_unique=0,
  'ALTER TABLE personnel ADD UNIQUE KEY uq_personnel_national_id (national_id)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
