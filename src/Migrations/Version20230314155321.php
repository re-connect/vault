<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230314155321 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE centre DROP FOREIGN KEY FK_C6A0EA756885AC1B');
        $this->addSql('ALTER TABLE centre ADD association_id INT DEFAULT NULL, CHANGE gestionnaire_id gestionnaire_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE centre ADD CONSTRAINT FK_C6A0EA75EFB9C8A5 FOREIGN KEY (association_id) REFERENCES association (id)');
        $this->addSql('ALTER TABLE centre ADD CONSTRAINT FK_C6A0EA756885AC1B FOREIGN KEY (gestionnaire_id) REFERENCES gestionnaire (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_C6A0EA75EFB9C8A5 ON centre (association_id)');
        $this->addSql('ALTER TABLE membre ADD wasGestionnaire TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE centre DROP FOREIGN KEY FK_C6A0EA75EFB9C8A5');
        $this->addSql('ALTER TABLE centre DROP FOREIGN KEY FK_C6A0EA756885AC1B');
        $this->addSql('DROP INDEX IDX_C6A0EA75EFB9C8A5 ON centre');
        $this->addSql('ALTER TABLE centre DROP association_id, CHANGE gestionnaire_id gestionnaire_id INT NOT NULL');
        $this->addSql('ALTER TABLE centre ADD CONSTRAINT FK_C6A0EA756885AC1B FOREIGN KEY (gestionnaire_id) REFERENCES gestionnaire (id)');
        $this->addSql('ALTER TABLE membre DROP wasGestionnaire');
    }
}
