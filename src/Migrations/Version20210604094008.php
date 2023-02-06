<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210604094008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop press table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE presse');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE presse (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, titre VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, imageName VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, link VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, canada TINYINT(1) DEFAULT \'0\' NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
    }
}
