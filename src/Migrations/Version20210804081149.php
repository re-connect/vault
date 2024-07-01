<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210804081149 extends AbstractMigration
{
    #[\Override]
    public function getDescription(): string
    {
        return 'Add createdAt property to faq_question table';
    }

    #[\Override]
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE faq_question ADD createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE faq_question DROP createdAt');
    }
}
