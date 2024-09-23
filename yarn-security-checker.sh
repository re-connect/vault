#!/bin/bash

# Check the output of yarn audit, return exit code 1 if high or critical vulnerabilities found

FAIL=0
SEVERITY_SUMMARY=$(yarn audit | tail -3 | head -2)
SEVERITY_COUNT=$(echo "$SEVERITY_SUMMARY" | tail -1)
HIGH_CRITICAL_COUNT_ARRAY=$(echo "$SEVERITY_COUNT" | awk '{print $5, $8, $11}' | tr ";" "\n")

for HIGH_CRITICAL_SEVERITY_COUNT in $HIGH_CRITICAL_COUNT_ARRAY; do
  if [ "$HIGH_CRITICAL_SEVERITY_COUNT" -ne 0 ]; then
    echo "$SEVERITY_SUMMARY"
    exit 1
  fi
done
