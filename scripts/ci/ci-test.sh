#!/bin/bash
# ── CI Tests ───────────────────────────────────────────────────────────────────
# Runs inside the test container through ci-docker.sh
# tests/v1 sequential, tests/v2 in parallel through paratest
set -e

# PHPUnit runs with APP_DEBUG=false, so its compiled container is never refreshed when routes, config or
# #[ApiResource] attributes change. A debug cache:clear is not enough: when sources changed, it rebuilds the debug
# container on boot, then considers the cache fresh and leaves the non-debug one in place.
APP_DEBUG=0 php bin/console cache:clear --env=test

make test-parallel PHP=php
