USE ihw;

-- Existing installations: prevent long parsed media text from breaking CSV imports.
ALTER TABLE asset_disks MODIFY media TEXT NULL;
