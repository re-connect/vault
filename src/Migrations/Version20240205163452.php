<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240205163452 extends AbstractMigration
{
    #[\Override]
    public function getDescription(): string
    {
        return '';
    }

    #[\Override]
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD hasPasswordWithLatestPolicy TINYINT(1) DEFAULT 0 NOT NULL');
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP hasPasswordWithLatestPolicy');
    }
}
