<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200825153131 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE beneficiaire_client');
        $this->addSql('ALTER TABLE client_entity ADD membre_distant_id INT UNSIGNED DEFAULT NULL COMMENT \'Identifier of the external initiator member (No entity link).\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE beneficiaire_client (distant_id INT UNSIGNED NOT NULL, beneficiaire_id INT NOT NULL, client_id INT NOT NULL, date_creation DATETIME NOT NULL, INDEX distant_id (distant_id), INDEX IDX_AAB81D0B5AF81F68 (beneficiaire_id), INDEX IDX_AAB81D0B19EB6921 (client_id), PRIMARY KEY(distant_id, beneficiaire_id, client_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE beneficiaire_client ADD CONSTRAINT FK_AAB81D0B19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE beneficiaire_client ADD CONSTRAINT FK_AAB81D0B5AF81F68 FOREIGN KEY (beneficiaire_id) REFERENCES beneficiaire (id)');
        $this->addSql('ALTER TABLE client_entity DROP membre_distant_id');
    }
}
