<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200819154712 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add external link to BeneficiaireCentre.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_entity ADD beneficiaire_centre_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE client_entity ADD CONSTRAINT FK_5B8E0FDBF15C33B FOREIGN KEY (beneficiaire_centre_id) REFERENCES beneficiairecentre (id)');
        $this->addSql('CREATE INDEX IDX_5B8E0FDBF15C33B ON client_entity (beneficiaire_centre_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_entity DROP FOREIGN KEY FK_5B8E0FDBF15C33B');
        $this->addSql('DROP INDEX IDX_5B8E0FDBF15C33B ON client_entity');
        $this->addSql('ALTER TABLE client_entity DROP beneficiaire_centre_id');
    }
}
