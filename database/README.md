# LOCO database setup and migration order

Use the database name `loco_db`. These steps are intended for the local WAMP/phpMyAdmin setup.

## If this is a brand-new database

1. Create/select `loco_db`.
2. Import the root-level `loco_db.sql` once.
3. Import `database/transaction_history.sql`.
4. Import `database/marketplace_database.sql`.
5. Import `database/community_inventory.sql`.
6. Import `database/loco_db_upgrade.sql`.
7. Import `database/community_workshop.sql`.

`community_workshop.sql` preserves the original Workshop tables and adds missing nullable ZIP/cover filename columns to existing `workshop_creations` records. It is safe to rerun after backing up the database. Existing creations without uploaded ZIPs remain visible but have no download link. ZIP uploads (max 10 MB) and optional JPG/PNG/WebP covers (max 2 MB) are stored under `Loco/uploads/workshop/`. Apache must allow PHP to write that directory. The included `.htaccess` blocks direct access to uploads; authenticated downloads are served through `workshop_download.php`. Treat community-uploaded ZIPs as untrusted files.

`community_inventory.sql` adds the inventory link to marketplace listings and backfills ownership for existing listings. The upgrade migration adds discussion replies, trade requests/history, reports, orders and order items, and the discussion tag column. The scripts use `CREATE TABLE IF NOT EXISTS` or conditional schema changes where applicable.

## If you already have data in loco_db

**Do not re-import the root-level `loco_db.sql` into your existing database.** It contains `DROP TABLE IF EXISTS` statements and can delete existing data.

Instead, make a backup first, then import only any migration files that have not already been applied, in the order above. Import `database/community_inventory.sql` after `database/marketplace_database.sql` so existing listings can be linked to their sellers' inventory. Existing migration tables are designed to be safe to create more than once, but still review phpMyAdmin's result messages and back up before running SQL.

## Verify after importing

Run this in phpMyAdmin's SQL tab:

```sql
SELECT DATABASE();

SHOW TABLES LIKE 'transaction_history';
SHOW TABLES LIKE 'marketplace_wishlists';
SHOW TABLES LIKE 'discussion_replies';
SHOW TABLES LIKE 'trade_requests';
SHOW TABLES LIKE 'trade_request_history';
SHOW TABLES LIKE 'marketplace_reports';
SHOW TABLES LIKE 'orders';
SHOW TABLES LIKE 'order_items';

SHOW COLUMNS FROM discussions LIKE 'tag';
SHOW COLUMNS FROM users LIKE 'wallet_balance';
SHOW COLUMNS FROM library LIKE 'game_id';
SHOW COLUMNS FROM marketplace_listings LIKE 'inventory_id';
SHOW TABLES LIKE 'inventories';
SHOW TABLES LIKE 'workshop_creations';
SHOW TABLES LIKE 'workshop_subscriptions';
```

Make sure the selected database is `loco_db` and each expected table/column is returned.

## Payment sandbox and checkout

`Loco/payment.php` supports either the internal LOCO wallet or the simulated LOCO Payment Sandbox for direct game purchases. On successful checkout, the game order, order items, library ownership, and transaction history are saved in one database transaction. Wallet purchases deduct the wallet balance; sandbox card purchases do not deduct wallet funds. If a database operation fails, the transaction is rolled back.

`Loco/wallet.php` uses the same sandbox simulation to add a selected demo amount to the user's wallet. The sandbox does not connect to PayPal or any real payment processor, does not charge real money, and does not store the submitted card number, expiry, or CVV. It accepts these clearly fictional test cards:

- Approved: `4242 4242 4242 4242`
- Declined: `4000 0000 0000 0002`

Use any current/future expiry month and a 3- or 4-digit test CVV. The declined test card should not create an order or add wallet balance.

Checkout expects all migrations above to have been applied. It has not been tested against your local WAMP/MySQL instance from this repository environment, so run the verification queries and test with a test account before relying on it.

## Community discussions and reports

`Loco/community.php` stores discussion posts in `discussions`, replies in `discussion_replies`, and marketplace reports in `marketplace_reports`. All are provided by the existing base/upgrade migrations; on an older database, make sure `database/loco_db_upgrade.sql` has been imported. Players may edit/delete only their own posts and replies. Reporting a listing creates a private, open report and the reporter can view their own report statuses under **My Reports**. There is no moderator interface yet; report review/resolution remains a future feature.

Test with two LOCO accounts: create a post as one, reply as the other, sign out and verify persistence. Verify users cannot edit each other's content, and that a report appears only to the reporting user.

## Administrator setup and moderation

1. Back up `loco_db`.
2. Import `database/community_admin.sql` in phpMyAdmin (after the base and upgrade migrations).
3. Confirm `SHOW COLUMNS FROM users LIKE 'role';` and `SHOW TABLES LIKE 'admin_audit_log';` return results.
4. Run `SELECT id,username,role FROM users;` and verify the correct account's numeric ID.
5. Promote only that trusted account with `UPDATE users SET role='admin' WHERE id=THE_VERIFIED_ID;`. Replace `THE_VERIFIED_ID` with the numeric ID; do **not** grant all users admin.
6. Log in as that account and visit `Loco/admin.php`, or use the **ADMIN** link in the navbar.

Every request to `admin.php` re-checks the role against the database; hiding the link is not the security boundary. Standard accounts receive HTTP 403. The admin can review report statuses and notes, remove listings, delete discussions, inspect a read-only user directory and see an audit log. The first version does **not** support account suspension, deleting users, or managing roles through the UI; report submissions do not automatically ban sellers. All moderation actions require a CSRF token. Test with two accounts and ensure a standard user cannot open the admin URL.

Do not re-import root `loco_db.sql` into an existing database; it drops tables.

## Public responses to marketplace reports

After importing `database/community_admin.sql`, import `database/community_admin_response.sql` once to add the nullable `marketplace_reports.admin_response` column. This upgrade preserves existing reports and private `moderator_notes`; it is safe to rerun. Back up `loco_db` first.

Administrators see a **Private moderator note** field and a separate **Response to reporter** field on the admin dashboard. The public response appears under the report in the reporting user's **My Reports** tab. Private moderator notes are not included in the My Reports query. An existing resolved report will show no public response until an administrator reopens its record in the dashboard and adds one. Audit logs record that notes/responses were provided, but not the confidential note text.
