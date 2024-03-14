<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240228153218 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE region (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE centre ADD region_id INT DEFAULT NULL, CHANGE region regionAsString VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE centre ADD CONSTRAINT FK_C6A0EA7598260155 FOREIGN KEY (region_id) REFERENCES region (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_C6A0EA7598260155 ON centre (region_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE centre DROP FOREIGN KEY FK_C6A0EA7598260155');
        $this->addSql('DROP TABLE region');
        $this->addSql('DROP INDEX IDX_C6A0EA7598260155 ON centre');
        $this->addSql('ALTER TABLE centre DROP region_id, CHANGE regionAsString region VARCHAR(255) DEFAULT NULL');
    }
}
