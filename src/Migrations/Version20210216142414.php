<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210216142414 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE centre CHANGE canada canada TINYINT(1) DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE partenaire CHANGE canada canada TINYINT(1) DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE presse CHANGE canada canada TINYINT(1) DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE user ADD fcnToken VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE centre CHANGE canada canada TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE partenaire CHANGE canada canada TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE presse CHANGE canada canada TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE user DROP fcnToken');
    }
}
