# LOCO database setup and migration order

Use the database name `loco_db`. These steps are intended for the local WAMP/phpMyAdmin setup.

## If this is a brand-new database

1. Create/select `loco_db`.
2. Import the root-level `loco_db.sql` once.
3. Import `database/transaction_history.sql`.
4. Import `database/marketplace_database.sql`.
5. Import `database/loco_db_upgrade.sql`.

The upgrade migration adds discussion replies, trade requests/history, reports, orders and order items, and the discussion tag column. The scripts use `CREATE TABLE IF NOT EXISTS` or a conditional column addition where applicable.

## If you already have data in loco_db

**Do not re-import the root-level `loco_db.sql` into your existing database.** It contains `DROP TABLE IF EXISTS` statements and can delete existing data.

Instead, make a backup first, then import only any migration files that have not already been applied, in the order above. Existing migration tables are designed to be safe to create more than once, but still review phpMyAdmin's result messages and back up before running SQL.

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
```

Make sure the selected database is `loco_db` and each expected table/column is returned.

## Checkout behavior

`Loco/payment.php` now uses database game IDs and prices, checks the logged-in user's wallet balance, and records the order, order lines, library ownership, and transaction history in one database transaction. If any part fails, the transaction is rolled back. The wallet is an internal demo balance only; no real payment provider is connected.

Checkout expects all migrations above to have been applied. It has not been tested against your local WAMP/MySQL instance from this repository environment, so run the verification queries and test with a low-cost/free game and a test account before relying on it.
