<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201209185321 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE centre ADD canada TINYINT(1) NOT NULL default (0)');
        $this->addSql('ALTER TABLE partenaire ADD canada TINYINT(1) NOT NULL default (0)');
        $this->addSql('ALTER TABLE presse ADD canada TINYINT(1) NOT NULL default (0)');
        $this->addSql('ALTER TABLE user ADD canada TINYINT(1) NOT NULL Default (0)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE centre DROP canada');
        $this->addSql('ALTER TABLE partenaire DROP canada');
        $this->addSql('ALTER TABLE presse DROP canada');
        $this->addSql('ALTER TABLE user DROP canada');
    }
}
