<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250416090732 extends AbstractMigration
{
    #[\Override]
    public function getDescription(): string
    {
        return '';
    }

    #[\Override]
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE client_entity CHANGE created_at createdAt DATETIME NOT NULL, CHANGE update_at updatedAt DATETIME NOT NULL');
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE client_entity CHANGE createdAt created_at DATETIME NOT NULL, CHANGE updatedAt update_at DATETIME NOT NULL');
    }
}
