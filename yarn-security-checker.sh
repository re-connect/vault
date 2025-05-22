#!/bin/bash

# Check the output of yarn audit, return exit code 1 if high or critical vulnerabilities found

FAIL=0
SEVERITIES=("Moderate")
SEVERITY_SUMMARY=$(yarn audit | tail -3 | head -2)
SEVERITY_COUNT=$(echo "$SEVERITY_SUMMARY" | tail -1)

for SEVERITY in "${SEVERITIES[@]}"; do
  if echo "$SEVERITY_COUNT" | grep -q "$SEVERITY"; then
    FAIL=1
  fi
done

if [ $FAIL -eq 1 ]; then
  echo "$SEVERITY_SUMMARY"
  exit 1
fi
