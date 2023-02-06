<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201016140022 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adding fields for subfolders.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier ADD dossier_parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE dossier ADD CONSTRAINT FK_3D48E037BC336E0D FOREIGN KEY (dossier_parent_id) REFERENCES dossier (id)');
        $this->addSql('CREATE INDEX IDX_3D48E037BC336E0D ON dossier (dossier_parent_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier DROP FOREIGN KEY FK_3D48E037BC336E0D');
        $this->addSql('DROP INDEX IDX_3D48E037BC336E0D ON dossier');
        $this->addSql('ALTER TABLE dossier DROP dossier_parent_id');
    }
}
