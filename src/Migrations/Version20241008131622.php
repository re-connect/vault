<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241008131622 extends AbstractMigration
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
        $this->addSql('ALTER TABLE dossier DROP FOREIGN KEY FK_3D48E03754B9D732');
        $this->addSql('ALTER TABLE dossier ADD CONSTRAINT FK_3D48E03754B9D732 FOREIGN KEY (icon_id) REFERENCES folder_icon (id) ON DELETE SET NULL');
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier DROP FOREIGN KEY FK_3D48E03754B9D732');
        $this->addSql('ALTER TABLE dossier ADD CONSTRAINT FK_3D48E03754B9D732 FOREIGN KEY (icon_id) REFERENCES folder_icon (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
