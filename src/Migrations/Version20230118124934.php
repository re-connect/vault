<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230118124934 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE creator ADD dossier_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE creator ADD CONSTRAINT FK_BC06EA63611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id)');
        $this->addSql('CREATE INDEX IDX_BC06EA63611C0C56 ON creator (dossier_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE creator DROP FOREIGN KEY FK_BC06EA63611C0C56');
        $this->addSql('DROP INDEX IDX_BC06EA63611C0C56 ON creator');
        $this->addSql('ALTER TABLE creator DROP dossier_id');
    }
}
