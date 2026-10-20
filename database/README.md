# LOCO database setup and migration order

Use the database name `loco_db`. These steps are intended for the local WAMP/phpMyAdmin setup.

## If this is a brand-new database

1. Create/select `loco_db`.
2. Import the root-level `loco_db.sql` once.
3. Import `database/transaction_history.sql`.
4. Import `database/marketplace_database.sql`.
5. Import `database/community_inventory.sql`.
6. Import `database/loco_db_upgrade.sql`.

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
```

Make sure the selected database is `loco_db` and each expected table/column is returned.

## Payment sandbox and checkout

`Loco/payment.php` supports either the internal LOCO wallet or the simulated LOCO Payment Sandbox for direct game purchases. On successful checkout, the game order, order items, library ownership, and transaction history are saved in one database transaction. Wallet purchases deduct the wallet balance; sandbox card purchases do not deduct wallet funds. If a database operation fails, the transaction is rolled back.

`Loco/wallet.php` uses the same sandbox simulation to add a selected demo amount to the user's wallet. The sandbox does not connect to PayPal or any real payment processor, does not charge real money, and does not store the submitted card number, expiry, or CVV. It accepts these clearly fictional test cards:

- Approved: `4242 4242 4242 4242`
- Declined: `4000 0000 0000 0002`

Use any current/future expiry month and a 3- or 4-digit test CVV. The declined test card should not create an order or add wallet balance.

Checkout expects all migrations above to have been applied. It has not been tested against your local WAMP/MySQL instance from this repository environment, so run the verification queries and test with a test account before relying on it.
