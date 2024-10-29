<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241029101121 extends AbstractMigration
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
        $this->addSql('ALTER TABLE annexe RENAME INDEX uniq_1bb35ba2f47645ae TO UNIQ_1C1F5E94F47645AE');
        $this->addSql('ALTER TABLE annexe RENAME INDEX uniq_1bb35ba29b76551f TO UNIQ_1C1F5E949B76551F');
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Annexe RENAME INDEX uniq_1c1f5e949b76551f TO UNIQ_1BB35BA29B76551F');
        $this->addSql('ALTER TABLE Annexe RENAME INDEX uniq_1c1f5e94f47645ae TO UNIQ_1BB35BA2F47645AE');
    }
}
