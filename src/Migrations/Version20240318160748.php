<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240318160748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE beneficiaire DROP isCreating');
        $this->addSql('ALTER TABLE user DROP isCreationProcessPending');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE beneficiaire ADD isCreating TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE user ADD isCreationProcessPending TINYINT(1) DEFAULT 0 NOT NULL');
    }
}
