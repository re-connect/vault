<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250424095245 extends AbstractMigration
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
        $this->addSql('ALTER TABLE evenement DROP heureRappel, DROP bEnvoye, DROP typeRappels, DROP archive');
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE evenement ADD heureRappel INT DEFAULT NULL, ADD bEnvoye TINYINT(1) NOT NULL, ADD typeRappels LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', ADD archive TINYINT(1) DEFAULT 0 NOT NULL');
    }
}
