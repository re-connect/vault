<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230522071608 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE centre DROP FOREIGN KEY FK_C6A0EA756885AC1B');
        $this->addSql('ALTER TABLE gestionnaire DROP FOREIGN KEY FK_F4461B20A76ED395');
        $this->addSql('ALTER TABLE gestionnaire DROP FOREIGN KEY FK_F4461B20EFB9C8A5');
        $this->addSql('DROP TABLE gestionnaire');
        $this->addSql('DROP INDEX IDX_C6A0EA756885AC1B ON centre');
        $this->addSql('ALTER TABLE centre DROP gestionnaire_id, CHANGE association_id association_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE gestionnaire (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, association_id INT NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, UNIQUE INDEX UNIQ_F4461B20A76ED395 (user_id), INDEX IDX_F4461B20EFB9C8A5 (association_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE gestionnaire ADD CONSTRAINT FK_F4461B20A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE gestionnaire ADD CONSTRAINT FK_F4461B20EFB9C8A5 FOREIGN KEY (association_id) REFERENCES association (id)');
        $this->addSql('ALTER TABLE centre ADD gestionnaire_id INT DEFAULT NULL, CHANGE association_id association_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE centre ADD CONSTRAINT FK_C6A0EA756885AC1B FOREIGN KEY (gestionnaire_id) REFERENCES gestionnaire (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_C6A0EA756885AC1B ON centre (gestionnaire_id)');
    }
}
