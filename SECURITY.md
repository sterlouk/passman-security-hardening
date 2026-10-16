# Security notes

passman is a **teaching application**. The course handed it out with deliberate vulnerabilities to find
and fix. It is meant for a local lab and should never be deployed on the internet. The `passman/xss/`
folder is attacker-side tooling for the XSS exercise (a cookie collector) and is not part of the app.

## What was fixed

### Assignment (tag `course-submission`)

| Vulnerability | Where | Exploit shown | Fix |
|---|---|---|---|
| SQL injection | `login.php`, `register.php`, `dashboard.php`, `notes.php` | `xxx' OR 1=1; -- ` on the login form logged in and dumped every user's saved credentials | Prepared statements with bound parameters for every query that takes user input |
| Plaintext login passwords | `register.php`, `login.php` | Anyone with database access could read all passwords | Salted SHA-256 hash (salt = SHA-256 of the username), using the course's helper |
| Plaintext stored credentials | `dashboard.php` | Website passwords readable straight from the `websites` table | AES-256-GCM, with a key derived per user via PBKDF2-SHA256 (100,000 iterations) from the login password, so ciphertext is useless without that password |
| Stored XSS | `notes.php`, `dashboard.php` | A note containing `<script>` ran in every other user's browser and sent their session cookie to the attacker page | All user-controlled output passed through `htmlspecialchars()` |
| Cleartext transport | Apache config (not in this repo) | — | HTTPS with a self-signed certificate (see the report) |

### Follow-up hardening (branch `security-hardening`)

Issues that were outside the assignment's scope but found during review:

| Issue | Impact | Fix |
|---|---|---|
| **IDOR on delete**: `DELETE ... WHERE webid=?` had no ownership check | Any logged-in user could delete any other user's saved passwords by changing the hidden `websiteid` field | The delete now also requires `login_user_id` to match the logged-in user |
| No CSRF protection | A malicious page could make a logged-in user add/delete entries or post notes | Per-session random token on every form, checked with `hash_equals()`; missing, wrong or malformed tokens get a 403 |
| Session fixation | `session_regenerate_id()` was commented out | Session ID is regenerated on login (login aborts if that fails); `session.use_strict_mode` rejects IDs the server did not issue |
| Session cookie readable by JavaScript | This is what made the XSS cookie theft possible | Cookie is `HttpOnly`, `SameSite=Strict`, and `Secure` when served over HTTPS |
| Fast password hash | One SHA-256 round can be brute-forced at billions of guesses per second on a GPU | `password_hash()` (bcrypt). Existing SHA-256 hashes still work and are upgraded on the user's next login |
| Encryption key derived before the password was checked | Key stored in the session even for failed logins | Key is derived only after a successful login and wiped on logout |
| Hard-coded `root` DB account with no password, raw MySQL errors shown to users | Leaks internals; app runs as DB superuser | Credentials from environment variables (Docker uses a dedicated non-root user); errors are logged, users see a generic message |
| Username casing changed the encryption key | The DB lookup ignores case, so logging in as `Alice` instead of `alice` worked but derived a different key, so saved entries became unreadable | Login continues with the username spelling stored at registration |
| Passwords over 72 bytes | bcrypt only uses the first 72 bytes, but the key comes from the whole password: two passwords sharing a 72-byte prefix would both log in, each with a different key | Passwords longer than 72 bytes are refused |
| No security headers | No defence-in-depth against XSS or clickjacking | `Content-Security-Policy`, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy` |

All fixes except HTTPS and the bcrypt migration are covered by [`tests/security_test.sh`](tests/security_test.sh),
an end-to-end suite that runs in CI against the Docker setup. The migration (old hash → bcrypt on login) was
checked by hand against an account created with the original code. Run against the `course-submission` tag, it fails the 12 checks for the follow-up
fixes and passes the ones for the assignment's fixes, so each test does detect the bug it targets.

## Known limitations (not fixed)

- **Keys live in server-side sessions.** While a user is logged in, their AES key is in the PHP session
  store on the server. Someone who compromises the server can decrypt the entries of logged-in users.
  A real password manager decrypts on the client and the server only ever sees ciphertext.
- **No brute-force protection.** No rate limiting, lockout or CAPTCHA on login, and no password policy.
- **Username enumeration.** Registration reports "user already exists".
- **No password change.** Changing a password would also require re-encrypting the user's saved entries
  with the new key. That feature doesn't exist.
- **Logout is a GET request**, so another site can log a user out (low impact).
- **Seed data** (`u1` / `p1`, `tompass`) is the course's plaintext sample data. That user can't log in,
  and the sample entry can't be decrypted.
- **Docker setup is HTTP only.** It's for local testing. For anything beyond that, put it behind TLS
  (and make sure PHP sees `HTTPS`, so the cookie gets the `Secure` flag).
- **Silent database write failures.** As in the original course code, a failed INSERT/DELETE isn't
  reported to the user.
- **The `xss/` lab tooling is itself vulnerable** (it stores and echoes attacker input unescaped, on
  purpose). It's left out of the Docker image, and should never be served next to the app.
