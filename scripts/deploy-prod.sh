#!/usr/bin/env bash

set -Eeuo pipefail

IMAGE="${1:?Docker image is required}"
ACTIVE="book-library-prod"
PREVIOUS="book-library-prod-previous"
ENV_FILE="/opt/book-library/.env"
SWAPPED=0

rollback() {
    RESULT=$?
    trap - EXIT

    if [ "$RESULT" -ne 0 ] && [ "$SWAPPED" -eq 1 ]; then
        echo "Production deployment failed. Starting rollback..."

        docker rm -f "$ACTIVE" || true

        if docker container inspect "$PREVIOUS" >/dev/null 2>&1; then
            if docker rename "$PREVIOUS" "$ACTIVE" && docker start "$ACTIVE"; then
                echo "Production rollback completed successfully."
            else
                echo "CRITICAL: Production rollback failed. Manual recovery required."
            fi
        else
            echo "CRITICAL: Previous Production container not found."
        fi
    fi

    exit "$RESULT"
}

trap rollback EXIT

echo "Downloading Production image..."
docker pull "$IMAGE"

test -f "$ENV_FILE"

docker container inspect "$ACTIVE" >/dev/null
test "$(docker inspect --format '{{.State.Running}}' "$ACTIVE")" = "true"

if docker container inspect "$PREVIOUS" >/dev/null 2>&1; then
    echo "Previous backup container already exists. Deployment cancelled."
    exit 1
fi

echo "Preserving current Production container..."

docker stop "$ACTIVE"

if ! docker rename "$ACTIVE" "$PREVIOUS"; then
    docker start "$ACTIVE"
    exit 1
fi

SWAPPED=1

echo "Starting new Production container..."

docker run -d \
    --name "$ACTIVE" \
    --restart unless-stopped \
    -p 80:80 \
    --env-file "$ENV_FILE" \
    "$IMAGE"

echo "Running Production health checks..."

HEALTHY=0

for i in {1..12}; do
    if curl -fsS --max-time 5 http://127.0.0.1/ >/dev/null &&
       curl -fsS --max-time 5 http://127.0.0.1/backend/health.php >/dev/null &&
       curl -fsS --max-time 5 http://127.0.0.1/backend/auth.php >/dev/null &&
       docker exec "$ACTIVE" php /opt/book-library/database/db_health.php >/dev/null; then

        HEALTHY=1
        break
    fi

    sleep 3
done

if [ "$HEALTHY" -ne 1 ]; then
    echo "Production health checks failed."
    exit 1
fi

echo "Production health checks passed."
echo "Finalising deployment..."

SWAPPED=0

docker rm "$PREVIOUS" || echo "Warning: Could not remove previous container."

echo "Production deployment completed successfully."