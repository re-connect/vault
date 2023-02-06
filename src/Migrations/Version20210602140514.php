<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210602140514 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create a verbatim table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE verbatim (id INT AUTO_INCREMENT NOT NULL, content VARCHAR(255) NOT NULL, author VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE verbatim');
    }
}
