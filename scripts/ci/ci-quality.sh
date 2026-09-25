#!/bin/bash
# ── CI Quality ─────────────────────────────────────────────────────────────────
# Runs inside the test container through ci-docker.sh
# Rector · CS Fixer · PHPStan
set -e

# Fail fast when composer.lock is out of sync with composer.json (content-hash
# mismatch, typically after a merge).
if ! composer validate --strict --no-check-all; then
    echo ""
    echo "❌ composer.lock désaligné avec composer.json."
    echo "   Corrige-le puis recommence :"
    echo "     make composer-lock-realign   # update --lock + validate + commit"
    exit 1
fi

# fixer-src and fixer-tests run in parallel: --quiet keeps their progress bars from interleaving
make rector PHP=php && make -j2 fixer-src fixer-tests PHP=php CS_FIXER_FLAGS="${CS_FIXER_FLAGS:---quiet}" && make stan PHP=php
