<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240924150243 extends AbstractMigration
{
    #[\Override]
    public function getDescription(): string
    {
        return '';
    }

    #[\Override]
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE shared_folder (id INT AUTO_INCREMENT NOT NULL, folder_id INT DEFAULT NULL, sharedAt DATETIME NOT NULL, expirationDate DATETIME NOT NULL, token LONGTEXT NOT NULL, selector VARCHAR(255) NOT NULL, sharedWithEmail VARCHAR(255) NOT NULL, sharedBy_id INT DEFAULT NULL, INDEX IDX_8F5A0BF9162CB942 (folder_id), INDEX IDX_8F5A0BF98CF51483 (sharedBy_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE shared_folder ADD CONSTRAINT FK_8F5A0BF9162CB942 FOREIGN KEY (folder_id) REFERENCES dossier (id)');
        $this->addSql('ALTER TABLE shared_folder ADD CONSTRAINT FK_8F5A0BF98CF51483 FOREIGN KEY (sharedBy_id) REFERENCES user (id)');
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shared_folder DROP FOREIGN KEY FK_8F5A0BF9162CB942');
        $this->addSql('ALTER TABLE shared_folder DROP FOREIGN KEY FK_8F5A0BF98CF51483');
        $this->addSql('DROP TABLE shared_folder');
    }
}
