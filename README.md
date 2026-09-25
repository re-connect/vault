# Reconnect Vault

Reconnect Vault is an online storage that lets people in precarious
situations digitise and secure their administrative, medical or personal documents.

Built with Symfony 7.4 and PHP 8.3.

## Requirements

- [Docker](https://docs.docker.com/get-docker/) (Compose v2.24.4 or later)
- make
- On Apple Silicon: the ClamAV image is amd64 only and runs emulated. Its first boot
  downloads the virus database and takes a few minutes.

## Quick start

```bash
make docker-nginx-hosts-install   # adds *.vault.local to /etc/hosts (asks for sudo)
make docker-main-up               # shared services + vault-main + vault-main-test
make docker-nginx-trust-cert      # trusts the self-signed certificate (asks for sudo)
```

The first run builds the image, installs Composer and Yarn dependencies, runs the
migrations and builds the assets: expect several minutes. The status table refreshes
every 3 seconds; wait for `prêt` in the Entrypoint column, then press Ctrl-C.

Then open <https://main.vault.local:8443>.

The database starts empty: no fixtures are loaded. Import your
own dump into the `vault_main`/`vault_dev` database, or load the test fixtures manually.

## Environments

The project has two long-lived branches, and one environment per branch:

- **`main`** is the main branch, deployed to production. `docker-main-*` is the default
  environment, the one to use unless you know you need the other one.
- **`dev`** is the integration branch, deployed to preprod before a production release. It
  may be ahead of `main`, which is what `docker-dev-*` is for.

Both environments mount the **same working copy**, so they run the code of the currently
checked out branch; only their databases, containers and ports differ. Switch branch and
environment together: `git switch dev && make docker-switch-dev`.

**Run only the environment you need, and switch when you need the other one**
(`make dsm` / `make dsd`). Running both at once is not recommended: they share the same
working copy and the same compiled assets in `public/`, and they consume twice the
resources for the same code. `make docker-all-up` does start them, one after the other
(they must not boot at the same time), but it is only useful to keep two datasets alive.

| | main (default) | dev |
|---|---|---|
| Git branch | `main` (production) | `dev` (preprod) |
| URL | <https://main.vault.local:8443> | <https://dev.vault.local:8443> |
| Container | `vault-main` | `vault-dev` |
| Database | `vault_main` | `vault_dev` |
| MinIO bucket | `vault-main` | `vault-dev` |
| App port (direct, no nginx) | 8012 | 8011 |
| Mailpit | <http://localhost:8029> | <http://localhost:8028> |
| Test container | `vault-main-test` | `vault-dev-test` |

Shared services, each in its own container and network so the project runs standalone:

| Service | Container | Host port |
|---|---|---|
| MariaDB 10.4 | `vault-db` | 3311 |
| MinIO (S3) | `vault-minio` | 9200 (API), 9201 (console) |
| nginx (TLS, routing) | `vault-nginx` | 8180 (HTTP), 8443 (HTTPS) |
| ClamAV | `vault-clamav` | — |

MinIO is also reachable through nginx: <https://minio.vault.local:8443> and
<https://console.minio.vault.local:8443> (`minioadmin` / `minioadmin`).

Test databases live in memory (tmpfs) and are thrown away when the test container stops.

Every published port is bound to `127.0.0.1`: the credentials above are public, so none of
these services is reachable from the local network.

## Common commands

`make` alone prints every target grouped by category. Short aliases are available.

| Command | What it does |
|---|---|
| `make ds` / `make dsl` | Container status (once / refreshing every 3s) |
| `make dmu` / `make ddu` | Start main / dev |
| `make dmd` / `make ddd` | Stop main / dev |
| `make dsm` / `make dsd` | Switch to main / dev (stops the other one) |
| `make dmr` / `make ddr` | Reload (replays the entrypoint) |
| `make dmbash` | Shell inside `vault-main` |
| `make dmlogs` | Symfony logs of `vault-main` |
| `make docker-main-entrypoint-logs` | Entrypoint logs (useful when a container stays "en attente") |
| `make docker-all-up` | Start main and dev one after the other (not recommended, see above) |
| `make docker-all-down` | Stop everything, shared services included |

Local configuration lives in `docker/dev/.env` and `docker/main/.env`. These files are
versioned and shared: they only contain local, non-secret values.

## Tests and quality

Run the whole suite the way CI does, inside the test container:

```bash
make ci-main    # or ci-dev
```

It runs `composer validate`, Rector, PHP-CS-Fixer, PHPStan, then the tests.

Individual targets (add `PHP=php` inside a container):

| Command | What it does |
|---|---|
| `make cs` | Rector + CS Fixer + PHPStan |
| `make test` | v1 then v2 fixtures and tests, sequentially |
| `make test-parallel` | v1 sequential, then v2 through paratest |
| `make db-test-parallel` | Creates and migrates the worker databases `vault_test1..N` |

`tests/v1` and `tests/v2` use incompatible fixtures, so each suite reloads its own
(with a purge) before running. Only `tests/v2` runs in parallel: `tests/v1` is 11 tests.
Worker count: `make test-parallel PARATEST_PROCESSES=8` (4 by default, also set in
`docker/*/.env.test.override`).

> paratest 6 and PHPUnit 9 are a deliberate, temporary choice: upgrading to PHPUnit 11
> and paratest 7 is part of the PHP 8.4 upgrade.

## Debugging with Xdebug

Xdebug is installed in the image and disabled by default. Set `XDEBUG_MODE=debug` in
`docker/main/.env` (or `docker/dev/.env`), then reload the environment with `make dmr`.
The container connects back to `host.docker.internal`.

## Setup without Docker

Supported, but not documented step by step. You will need PHP 8.3 with the `apcu`,
`imagick`, `gd`, `exif`, `xsl`, `intl`, `zip` and `pdo_mysql` extensions (plus
`apc.enable_cli=1`), MariaDB, Yarn 1 and the Symfony CLI. Copy the OAuth test keys with
`mkdir -p var/oauth && cp tests/keys/* var/oauth`, then run the migrations and
`make ci` to check the setup.

See [Basic Symfony/MySQL project setup steps](https://github.com/re-connect/symfony-project-setup).
