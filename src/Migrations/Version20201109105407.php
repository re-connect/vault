<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201109105407 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Delete the "typedocument" and "categorie" columns from document with their table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A76BCF5E72D');
        $this->addSql('ALTER TABLE typedocument DROP FOREIGN KEY FK_840004FEBCF5E72D');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A763BEBD1BD');
        $this->addSql('DROP TABLE categorie');
        $this->addSql('DROP TABLE typedocument');
        $this->addSql('DROP INDEX IDX_D8698A76BCF5E72D ON document');
        $this->addSql('DROP INDEX IDX_D8698A763BEBD1BD ON document');
        $this->addSql('ALTER TABLE document DROP categorie_id, DROP typeDocument_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categorie (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE typedocument (id INT AUTO_INCREMENT NOT NULL, categorie_id INT DEFAULT NULL, nom VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, dureeDeVie INT DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, INDEX IDX_840004FEBCF5E72D (categorie_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE typedocument ADD CONSTRAINT FK_840004FEBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE document ADD categorie_id INT DEFAULT NULL, ADD typeDocument_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A763BEBD1BD FOREIGN KEY (typeDocument_id) REFERENCES typedocument (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id)');
        $this->addSql('CREATE INDEX IDX_D8698A76BCF5E72D ON document (categorie_id)');
        $this->addSql('CREATE INDEX IDX_D8698A763BEBD1BD ON document (typeDocument_id)');
    }
}
