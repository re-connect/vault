<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240819143441 extends AbstractMigration
{
    #[\Override]
    public function getDescription(): string
    {
        return '';
    }

    #[\Override]
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE folder_icon (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, fileName VARCHAR(255) NOT NULL, updatedAt DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE dossier ADD icon_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE dossier ADD CONSTRAINT FK_3D48E03754B9D732 FOREIGN KEY (icon_id) REFERENCES folder_icon (id)');
        $this->addSql('CREATE INDEX IDX_3D48E03754B9D732 ON dossier (icon_id)');
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dossier DROP FOREIGN KEY FK_3D48E03754B9D732');
        $this->addSql('DROP TABLE folder_icon');
        $this->addSql('DROP INDEX IDX_3D48E03754B9D732 ON dossier');
        $this->addSql('ALTER TABLE dossier DROP icon_id');
    }
}
