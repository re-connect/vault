<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * One-off cleanup: truncate the OAuth2 token tables.
 *
 * oauth2_access_token grew to ~58M rows / ~19.6 GB because expired tokens were
 * never purged. Access tokens live 1h, so nearly every row is expired.
 *
 * TRUNCATE is near-instant and returns the disk space to the OS immediately
 * (no OPTIMIZE TABLE, no extra free space needed). It wipes ALL tokens,
 * including still-valid ones, so active OAuth sessions are dropped: run this in
 * a maintenance window. Foreign key checks are disabled because
 * oauth2_refresh_token references oauth2_access_token (ON DELETE SET NULL).
 */
final class Version20260805100000 extends AbstractMigration
{
    #[\Override]
    public function getDescription(): string
    {
        return 'Truncate oauth2_access_token and oauth2_refresh_token (one-off purge of never-cleaned expired tokens)';
    }

    #[\Override]
    public function isTransactional(): bool
    {
        // TRUNCATE is DDL (implicit commit in MySQL); do not wrap in a transaction.
        return false;
    }

    #[\Override]
    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('TRUNCATE TABLE oauth2_refresh_token');
        $this->addSql('TRUNCATE TABLE oauth2_access_token');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        // Irreversible: truncated token data cannot be restored.
        $this->throwIrreversibleMigrationException('Truncated OAuth2 tokens cannot be restored.');
    }
}
