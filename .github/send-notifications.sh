#!/bin/bash

JSON_PAYLOAD=$(cat <<EOF
{
  "application": "PES",
  "sys_name": "prestashop $ACTION_NAME",
  "deployer": "$PUSHER_NAME",
  "files_changed_count": $(echo git show --name-only --format= $GITHUB_SHA | wc -l),
  "last_commit_message": $(echo -n "$COMMIT_MESSAGE" | jq -Rsa 'tojson'),
  "last_commit_hash": "$GITHUB_SHA",
  "timestamp": "$(date --utc +%Y-%m-%dT%H:%M:%SZ)"
}
EOF
)

curl -sS -X POST \
  -H "Content-Type: application/json" \
  -d "$JSON_PAYLOAD" \
  "$WEBHOOK_URL" > /dev/null
