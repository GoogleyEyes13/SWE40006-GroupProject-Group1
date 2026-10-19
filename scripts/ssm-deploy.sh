#!/usr/bin/env bash

set -Eeuo pipefail

INSTANCE_ID="${1:?Instance ID is required}"
ENVIRONMENT="${2:?Environment is required}"
IMAGE="${3:?Docker image is required}"

if [[ "$IMAGE" != *@sha256:* ]]; then
    echo "Image must be referenced by digest: $IMAGE"
    exit 1
fi

REGISTRY="${IMAGE%%/*}"
SCRIPT_BASE64=$(base64 -w 0 scripts/deploy.sh)

echo "Deploying $IMAGE to $ENVIRONMENT"

bash scripts/ssm-run.sh "$INSTANCE_ID" \
    "set -e" \
    "aws ecr get-login-password --region $AWS_REGION | docker login --username AWS --password-stdin $REGISTRY" \
    "printf %s $SCRIPT_BASE64 | base64 -d > /tmp/book-library-deploy.sh" \
    "bash /tmp/book-library-deploy.sh $ENVIRONMENT $IMAGE"
