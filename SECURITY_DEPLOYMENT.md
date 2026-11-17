# LOCO security and redeployment checklist

This project is a **school payment simulation**. Do not enter or collect real card numbers, CVVs, GCash PINs, or PayPal passwords.

## Required before testing or deploying

1. Restore the MySQL backup to your new host.
2. **Import `database/login_rate_limits.sql` in phpMyAdmin.** The updated login fails closed if the rate-limit table is missing.
3. Configure `LOCO_DB_HOST`, `LOCO_DB_PORT`, `LOCO_DB_NAME`, `LOCO_DB_USER`, and `LOCO_DB_PASS` on the server. Keep production passwords out of GitHub. Rotate the old InfinityFree database password.
4. Use HTTPS for the live site. `Loco/session.php` marks cookies Secure only on HTTPS, so local HTTP WAMP development still works.
5. Confirm the host supports PHP PDO MySQL, fileinfo, and **ZipArchive**. Workshop ZIP uploads fail closed if PHP's zip extension is missing.
6. Confirm direct access to `Loco/uploads/avatars/` and `Loco/uploads/workshop/` is denied. Apache uses the included `.htaccess` files; other web servers need equivalent rules.
7. Confirm `Loco/images/default_avatar.png` exists with **exactly that case and name**, or update the references to your actual image filename.
8. Compare the InfinityFree backup against a known-good copy and scan for unexpected files before redeployment. These changes do not establish why Safe Browsing flagged the old site.

## Test cases

- Register with a valid email, valid birthday, and an 8+ character password. Invalid dates and short passwords should be rejected.
- Log in successfully, navigate through Store, Library, Community, and Wallet, then log out.
- Submit login and registration forms with an invalid CSRF token: they must be rejected.
- On a test account, try eight incorrect passwords within 15 minutes: subsequent sign-ins should be rate-limited. The per-IP threshold is 40 failed attempts in 15 minutes. Use a fresh test account if you need to test the lockout.
- Upload a normal Workshop ZIP and verify the download. Reject ZIPs with `../` paths, executable files, encrypted entries, too many files, or excessive expanded size.
- Test simulated wallet top-ups and purchases; no real card details should be requested.
- Check the browser's session cookie for HttpOnly, SameSite=Lax, and Secure on HTTPS.

## Maintenance

Login throttle counters are stored as SHA-256 digests in `login_rate_limits`. To clean up old records, run periodically:

```sql
DELETE FROM login_rate_limits
WHERE window_started_at < UTC_TIMESTAMP() - INTERVAL 2 DAY;
```

The code and migration are intended for MySQL/MariaDB. These are static code changes; perform PHP linting and functional tests on your WAMP installation and new host before using the site publicly.
