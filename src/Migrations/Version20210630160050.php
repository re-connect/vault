<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210630160050 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create shareddocument table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE shareddocument (id INT AUTO_INCREMENT NOT NULL, document_id INT DEFAULT NULL, sharedAt DATETIME NOT NULL, expirationDate DATETIME NOT NULL, token LONGTEXT NOT NULL, selector VARCHAR(255) NOT NULL, sharedWithEmail VARCHAR(255) NOT NULL, sharedBy_id INT DEFAULT NULL, INDEX IDX_8DF667BCC33F7837 (document_id), INDEX IDX_8DF667BC8CF51483 (sharedBy_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE shareddocument ADD CONSTRAINT FK_8DF667BCC33F7837 FOREIGN KEY (document_id) REFERENCES document (id)');
        $this->addSql('ALTER TABLE shareddocument ADD CONSTRAINT FK_8DF667BC8CF51483 FOREIGN KEY (sharedBy_id) REFERENCES user (id)');
        $this->addSql('DROP TABLE sharedocument');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sharedocument (id INT AUTO_INCREMENT NOT NULL, document_id INT DEFAULT NULL, beneficiaire_id INT DEFAULT NULL, sharedAt DATETIME NOT NULL, expirationDate DATETIME NOT NULL, token LONGTEXT CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, verifier VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, sharedWithEmail VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, INDEX IDX_EE4DC1975AF81F68 (beneficiaire_id), INDEX IDX_EE4DC197C33F7837 (document_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE sharedocument ADD CONSTRAINT FK_EE4DC1975AF81F68 FOREIGN KEY (beneficiaire_id) REFERENCES beneficiaire (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE sharedocument ADD CONSTRAINT FK_EE4DC197C33F7837 FOREIGN KEY (document_id) REFERENCES document (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('DROP TABLE shareddocument');
    }
}
