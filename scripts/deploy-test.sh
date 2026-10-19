#!/usr/bin/env bash

set -Eeuo pipefail

IMAGE="${1:?Docker image is required}"
ACTIVE="book-library-test"
PREVIOUS="book-library-test-previous"
ENV_FILE="/opt/book-library/.env"
SMOKE_FILE="$(mktemp /tmp/book-library-smoke.XXXXXX)"
SWAPPED=0

cleanup() {
    RESULT=$?
    trap - EXIT

    rm -f "$SMOKE_FILE"

    if [ "$RESULT" -ne 0 ] && [ "$SWAPPED" -eq 1 ]; then
        echo "Deployment failed. Starting automatic rollback..."

        docker rm -f "$ACTIVE" || true

        if docker container inspect "$PREVIOUS" >/dev/null 2>&1; then
            if docker rename "$PREVIOUS" "$ACTIVE" && docker start "$ACTIVE"; then
                echo "Previous container restored successfully."
            else
                echo "CRITICAL: Automatic rollback failed."
            fi
        else
            echo "CRITICAL: Previous container not found."
        fi
    fi

    exit "$RESULT"
}

trap cleanup EXIT

echo "Downloading new Docker image..."
docker pull "$IMAGE"

docker container inspect "$ACTIVE" >/dev/null
test -f "$ENV_FILE"

if docker container inspect "$PREVIOUS" >/dev/null 2>&1; then
    echo "Previous backup container already exists. Deployment cancelled."
    exit 1
fi

echo "Preserving current container..."
docker stop "$ACTIVE"

if ! docker rename "$ACTIVE" "$PREVIOUS"; then
    docker start "$ACTIVE"
    exit 1
fi

SWAPPED=1

echo "Starting new container..."

docker run -d \
    --name "$ACTIVE" \
    --restart unless-stopped \
    -p 80:80 \
    --env-file "$ENV_FILE" \
    "$IMAGE"

echo "Checking application health..."

HEALTHY=0

for i in {1..12}; do
    if curl -fsS --max-time 5 http://127.0.0.1/ >/dev/null &&
       curl -fsS --max-time 5 http://127.0.0.1/backend/health.php >/dev/null; then
        HEALTHY=1
        break
    fi

    sleep 3
done

if [ "$HEALTHY" -ne 1 ]; then
    echo "Health checks failed."
    exit 1
fi

echo "Running API integration tests..."

docker cp "$ACTIVE:/opt/book-library/tests/api_smoke.py" "$SMOKE_FILE"
python3 "$SMOKE_FILE"

echo "All tests passed. Finalising deployment..."

SWAPPED=0
docker rm "$PREVIOUS" || echo "Warning: Previous container could not be removed."

echo "Deployment completed successfully."