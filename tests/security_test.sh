#!/usr/bin/env bash
# End-to-end security regression tests for passman.
# Runs black-box against a live server: ./tests/security_test.sh [base_url]
set -u

BASE="${1:-http://127.0.0.1:8080/passman}"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
RUN="$(date +%s)$RANDOM"
ALICE="alice$RUN"
BOB="bob$RUN"
PASS=0
FAIL=0

ok()   { echo "  PASS  $1"; PASS=$((PASS + 1)); }
bad()  { echo "  FAIL  $1"; FAIL=$((FAIL + 1)); }
check() { if eval "$2"; then ok "$1"; else bad "$1"; fi; }

# GET a page with a cookie jar and print its CSRF token
token() {
	curl -s -c "$1" -b "$1" "$BASE/$2" | grep -o "name='csrf_token' value='[0-9a-f]*'" | head -1 | sed "s/.*value='//; s/'//"
}

# POST with a fresh CSRF token; prints "<http code> <redirect url>"
post() {
	local jar="$1" page="$2"; shift 2
	local t; t="$(token "$jar" "$page")"
	curl -s -o /dev/null -c "$jar" -b "$jar" -w "%{http_code} %{redirect_url}" --data-urlencode "csrf_token=$t" "$@" "$BASE/$page"
}

login() {
	post "$TMP/$1" login.php --data-urlencode "username=$1" --data-urlencode "password=$2"
}

session_id() { grep PHPSESSID "$1" | awk '{print $7}'; }

echo "Target: $BASE"

echo "[Setup]"
check "register alice" '[[ "$(post "$TMP/reg" register.php -d "new_username=$ALICE&new_password=alicepw")" == 200* ]]'
check "register bob"   '[[ "$(post "$TMP/reg2" register.php -d "new_username=$BOB&new_password=bobpw")" == 200* ]]'

echo "[SQL injection]"
r="$(post "$TMP/sqli" login.php --data-urlencode "username=xxx' OR 1=1; -- " -d "password=x")"
check "login bypass with ' OR 1=1 is rejected" '[[ "$r" != *dashboard.php* ]]'

echo "[Session handling]"
headers="$(curl -s -D - -o /dev/null "$BASE/login.php")"
check "session cookie is HttpOnly"        'grep -qi "set-cookie:.*httponly" <<<"$headers"'
check "session cookie is SameSite=Strict" 'grep -qi "set-cookie:.*samesite=strict" <<<"$headers"'
check "Content-Security-Policy header set" 'grep -qi "content-security-policy:" <<<"$headers"'
check "clickjacking protection set"        'grep -qi "x-frame-options: deny" <<<"$headers"'
token "$TMP/$ALICE" login.php >/dev/null
before="$(session_id "$TMP/$ALICE")"
check "alice can log in" '[[ "$(login "$ALICE" alicepw)" == *dashboard.php* ]]'
after="$(session_id "$TMP/$ALICE")"
check "session ID changes on login (no fixation)" '[[ -n "$before" && -n "$after" && "$before" != "$after" ]]'
check "wrong password is rejected" '[[ "$(login "$BOB" wrongpw)" != *dashboard.php* ]]'
check "bob can log in" '[[ "$(login "$BOB" bobpw)" == *dashboard.php* ]]'

echo "[Stored credentials]"
post "$TMP/$ALICE" dashboard.php -d "new_website=bank.example&new_username=alice&new_password=S3cret-$RUN" >/dev/null
page="$(curl -s -b "$TMP/$ALICE" "$BASE/dashboard.php")"
check "alice sees her decrypted entry" 'grep -q "S3cret-$RUN" <<<"$page"'
webid="$(grep -o "name='websiteid' value='[0-9]*'" <<<"$page" | tail -1 | grep -o "[0-9]*'$" | tr -d "'")"

echo "[Access control]"
post "$TMP/$BOB" dashboard.php -d "delete_website=1&websiteid=$webid" >/dev/null
check "bob cannot delete alice's entry (IDOR)" 'curl -s -b "$TMP/$ALICE" "$BASE/dashboard.php" | grep -q "S3cret-$RUN"'
check "bob cannot see alice's entry" '! curl -s -b "$TMP/$BOB" "$BASE/dashboard.php" | grep -q "S3cret-$RUN"'

echo "[CSRF]"
code="$(curl -s -o /dev/null -w "%{http_code}" -b "$TMP/$ALICE" -d "delete_website=1&websiteid=$webid" "$BASE/dashboard.php")"
check "delete without CSRF token is refused (403)" '[[ "$code" == 403 ]]'
code="$(curl -s -o /dev/null -w "%{http_code}" -b "$TMP/$ALICE" -d "delete_website=1&websiteid=$webid&csrf_token=forged" "$BASE/dashboard.php")"
check "delete with forged CSRF token is refused (403)" '[[ "$code" == 403 ]]'
check "entry survived both forged requests" 'curl -s -b "$TMP/$ALICE" "$BASE/dashboard.php" | grep -q "S3cret-$RUN"'
post "$TMP/$ALICE" dashboard.php -d "delete_website=1&websiteid=$webid" >/dev/null
check "alice can delete her own entry" '! curl -s -b "$TMP/$ALICE" "$BASE/dashboard.php" | grep -q "S3cret-$RUN"'

echo "[Stored XSS]"
post "$TMP/$BOB" notes.php --data-urlencode "new_note=<script>alert('xss$RUN')</script>" >/dev/null
notes="$(curl -s -b "$TMP/$ALICE" "$BASE/notes.php")"
check "malicious note is shown escaped" 'grep -q "&lt;script&gt;alert(&#039;xss$RUN&#039;)" <<<"$notes"'
check "malicious note is not executable" '! grep -q "<script>alert(.xss$RUN" <<<"$notes"'

echo "[Logout]"
curl -s -o /dev/null -c "$TMP/$ALICE" -b "$TMP/$ALICE" "$BASE/logout.php"
r="$(curl -s -o /dev/null -w "%{redirect_url}" -b "$TMP/$ALICE" "$BASE/dashboard.php")"
check "dashboard requires login after logout" '[[ "$r" == *login.php* ]]'

echo
echo "$PASS passed, $FAIL failed"
[[ $FAIL -eq 0 ]]
