<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250820140123 extends AbstractMigration
{
    #[\Override]
    public function getDescription(): string
    {
        return '';
    }

    #[\Override]
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE creator ADD centre_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE creator ADD CONSTRAINT FK_BC06EA63463CD7C3 FOREIGN KEY (centre_id) REFERENCES centre (id)');
        $this->addSql('CREATE INDEX IDX_BC06EA63463CD7C3 ON creator (centre_id)');
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE creator DROP FOREIGN KEY FK_BC06EA63463CD7C3');
        $this->addSql('DROP INDEX IDX_BC06EA63463CD7C3 ON creator');
        $this->addSql('ALTER TABLE creator DROP centre_id');
    }
}
