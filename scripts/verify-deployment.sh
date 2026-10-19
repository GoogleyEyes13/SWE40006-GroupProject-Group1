#!/usr/bin/env bash

set -Eeuo pipefail

URL="${1:?URL is required}"
EXPECTED_VERSION="${2:?Expected version is required}"

fetch() {
    curl -fsS --retry 5 --retry-all-errors --retry-delay 5 --max-time 15 "$URL$1"
}

echo "Verifying $URL"

for page in / /library.html /mycollection.html /backend/auth.php; do
    fetch "$page" >/dev/null
    echo "OK   $page"
done

VERSION=$(fetch /backend/health.php | jq -r '.version')

if [ "$VERSION" != "$EXPECTED_VERSION" ]; then
    echo "FAIL $URL is running version $VERSION, expected $EXPECTED_VERSION"
    exit 1
fi

echo "OK   running version $VERSION"

fetch "/backend/search.php?q=tolkien" | jq -e '.docs | length > 0' >/dev/null
echo "OK   book search"

STATUS=$(curl -s -o /dev/null -w '%{http_code}' --max-time 15 "$URL/backend/library.php")

if [ "$STATUS" != "401" ]; then
    echo "FAIL personal library returned $STATUS without logging in, expected 401"
    exit 1
fi

echo "OK   personal library requires login"
echo "Deployment verified."
