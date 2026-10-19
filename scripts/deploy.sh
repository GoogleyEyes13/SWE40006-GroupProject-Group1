#!/usr/bin/env bash

set -Eeuo pipefail

ENVIRONMENT="${1:?Environment is required}"
IMAGE="${2:?Docker image is required}"

case "$ENVIRONMENT" in
    test) ACTIVE="book-library-test" ;;
    production) ACTIVE="book-library-prod" ;;
    *) echo "Unknown environment: $ENVIRONMENT"; exit 1 ;;
esac

PREVIOUS="$ACTIVE-previous"
ENV_FILE="/opt/book-library/.env"
SMOKE_FILE=""
SWAPPED=0
HAS_PREVIOUS=0
FAILED_CHECK=""

app_healthy() {
    for path in / /backend/health.php /backend/auth.php; do
        if ! curl -fsS --max-time 5 "http://127.0.0.1$path" >/dev/null 2>&1; then
            FAILED_CHECK="GET $path"
            return 1
        fi
    done

    if ! docker exec "$ACTIVE" php /opt/book-library/database/db_health.php >/dev/null 2>&1; then
        FAILED_CHECK="database connection"
        return 1
    fi
}

wait_until_healthy() {
    for _ in {1..12}; do
        if app_healthy; then
            return 0
        fi

        sleep 5
    done

    return 1
}

rollback() {
    RESULT=$?
    trap - EXIT

    if [ -n "$SMOKE_FILE" ]; then
        rm -f "$SMOKE_FILE"
    fi

    if [ "$RESULT" -ne 0 ] && [ "$SWAPPED" -eq 1 ]; then
        echo "Deployment failed. Starting automatic rollback..."

        docker logs --tail 20 "$ACTIVE" || true
        docker rm -f "$ACTIVE" >/dev/null 2>&1 || true

        if [ "$HAS_PREVIOUS" -eq 0 ]; then
            echo "CRITICAL: No previous container to restore."
        elif docker rename "$PREVIOUS" "$ACTIVE" && docker start "$ACTIVE" >/dev/null; then
            if wait_until_healthy; then
                echo "Rollback completed. Previous version is running."
            else
                echo "CRITICAL: Previous version restored but failing: $FAILED_CHECK"
            fi
        else
            echo "CRITICAL: Rollback failed. Manual recovery required."
        fi
    fi

    exit "$RESULT"
}

trap rollback EXIT

if [ ! -f "$ENV_FILE" ]; then
    echo "Missing $ENV_FILE"
    exit 1
fi

if docker container inspect "$PREVIOUS" >/dev/null 2>&1; then
    echo "$PREVIOUS already exists from an unfinished deployment. Deployment cancelled."
    exit 1
fi

echo "Downloading $IMAGE..."
docker pull -q "$IMAGE"

if docker container inspect "$ACTIVE" >/dev/null 2>&1; then
    echo "Preserving current container..."
    docker stop "$ACTIVE" >/dev/null

    if ! docker rename "$ACTIVE" "$PREVIOUS"; then
        docker start "$ACTIVE" >/dev/null
        exit 1
    fi

    HAS_PREVIOUS=1
else
    echo "No $ACTIVE container found. Starting a fresh deployment."
fi

SWAPPED=1

echo "Starting new container..."

docker run -d \
    --name "$ACTIVE" \
    --restart unless-stopped \
    -p 80:80 \
    --env-file "$ENV_FILE" \
    "$IMAGE" >/dev/null

echo "Running health checks..."

if ! wait_until_healthy; then
    echo "Health checks failed: $FAILED_CHECK"
    exit 1
fi

echo "Health checks passed."

if [ "$ENVIRONMENT" = "test" ]; then
    echo "Running API integration tests..."

    SMOKE_FILE=$(mktemp /tmp/book-library-smoke.XXXXXX)
    docker cp "$ACTIVE:/opt/book-library/tests/api_smoke.py" "$SMOKE_FILE"
    python3 "$SMOKE_FILE"
fi

echo "Finalising deployment..."

SWAPPED=0

if [ "$HAS_PREVIOUS" -eq 1 ]; then
    docker rm "$PREVIOUS" >/dev/null || echo "Warning: Could not remove $PREVIOUS."
fi

docker image prune -af >/dev/null || echo "Warning: Could not remove old images."

echo "Deployment to $ENVIRONMENT completed successfully."
