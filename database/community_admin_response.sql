-- LOCO public moderator response for marketplace reports.
-- Apply to loco_db after database/community_admin.sql; safe to rerun.
-- Existing reports and private moderator_notes are preserved.
SET @add_public_report_reply = (
 SELECT IF(COUNT(*)=0,
 'ALTER TABLE marketplace_reports ADD COLUMN admin_response TEXT NULL',
 'SELECT 1')
 FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA=DATABASE()
 AND TABLE_NAME='marketplace_reports'
 AND COLUMN_NAME='admin_response'
);
PREPARE loco_report_reply_stmt FROM @add_public_report_reply;
EXECUTE loco_report_reply_stmt;
DEALLOCATE PREPARE loco_report_reply_stmt;

-- Existing resolved reports keep NULL response until an admin supplies one.
