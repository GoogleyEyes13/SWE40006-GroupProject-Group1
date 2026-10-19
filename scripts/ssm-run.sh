#!/usr/bin/env bash

set -Eeuo pipefail

INSTANCE_ID="${1:?Instance ID is required}"
shift

PARAMETERS=$(jq -nc '{commands: $ARGS.positional, executionTimeout: ["900"]}' --args "$@")

COMMAND_ID=$(aws ssm send-command \
    --instance-ids "$INSTANCE_ID" \
    --document-name "AWS-RunShellScript" \
    --parameters "$PARAMETERS" \
    --query "Command.CommandId" \
    --output text)

echo "Command $COMMAND_ID sent to $INSTANCE_ID"

STATUS="Pending"

while [[ "$STATUS" =~ ^(Pending|InProgress|Delayed)$ ]]; do
    sleep 5

    STATUS=$(aws ssm get-command-invocation \
        --command-id "$COMMAND_ID" \
        --instance-id "$INSTANCE_ID" \
        --query Status \
        --output text 2>/dev/null || echo "Pending")
done

aws ssm get-command-invocation \
    --command-id "$COMMAND_ID" \
    --instance-id "$INSTANCE_ID" \
    --query '[StandardOutputContent,StandardErrorContent]' \
    --output text

echo "Command finished with status: $STATUS"

test "$STATUS" = "Success"
