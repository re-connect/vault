<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221114160444 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document CHANGE extension extension VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dossier DROP FOREIGN KEY FK_3D48E037BC336E0D');
        $this->addSql('ALTER TABLE dossier ADD CONSTRAINT FK_3D48E037BC336E0D FOREIGN KEY (dossier_parent_id) REFERENCES dossier (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier DROP FOREIGN KEY FK_3D48E037BC336E0D');
        $this->addSql('ALTER TABLE dossier ADD CONSTRAINT FK_3D48E037BC336E0D FOREIGN KEY (dossier_parent_id) REFERENCES dossier (id)');
        $this->addSql('ALTER TABLE document CHANGE extension extension VARCHAR(10) NOT NULL');
    }
}
