<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250310142443 extends AbstractMigration
{
    #[\Override]
    public function getDescription(): string
    {
        return '';
    }

    #[\Override]
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE beneficiaire DROP FOREIGN KEY FK_B140D802B660D3F4');
        $this->addSql('DROP INDEX IDX_B140D802B660D3F4 ON beneficiaire');
        $this->addSql('ALTER TABLE beneficiaire DROP creePar_id, CHANGE totalFileSize totalFileSize INT DEFAULT NULL');
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE beneficiaire ADD creePar_id INT DEFAULT NULL, CHANGE totalFileSize totalFileSize INT DEFAULT 0');
        $this->addSql('ALTER TABLE beneficiaire ADD CONSTRAINT FK_B140D802B660D3F4 FOREIGN KEY (creePar_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_B140D802B660D3F4 ON beneficiaire (creePar_id)');
    }
}
