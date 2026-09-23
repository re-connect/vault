#!/bin/bash
# ── CI Tests ───────────────────────────────────────────────────────────────────
# Runs inside the test container through ci-docker.sh
# tests/v1 sequential, tests/v2 in parallel through paratest
set -e

make test-parallel PHP=php
