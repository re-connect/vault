<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210628153852 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create sharedocument table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sharedocument (id INT AUTO_INCREMENT NOT NULL, document_id INT DEFAULT NULL, beneficiaire_id INT DEFAULT NULL, sharedAt DATETIME NOT NULL, expirationDate DATETIME NOT NULL, token LONGTEXT NOT NULL, verifier VARCHAR(255) NOT NULL, sharedWithEmail VARCHAR(255) NOT NULL, INDEX IDX_EE4DC197C33F7837 (document_id), INDEX IDX_EE4DC1975AF81F68 (beneficiaire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sharedocument ADD CONSTRAINT FK_EE4DC197C33F7837 FOREIGN KEY (document_id) REFERENCES document (id)');
        $this->addSql('ALTER TABLE sharedocument ADD CONSTRAINT FK_EE4DC1975AF81F68 FOREIGN KEY (beneficiaire_id) REFERENCES beneficiaire (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE sharedocument');
    }
}
