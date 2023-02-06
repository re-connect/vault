<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230130164930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD disabledAt DATETIME DEFAULT NULL, ADD disabledBy_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6493F1E31AA FOREIGN KEY (disabledBy_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_8D93D6493F1E31AA ON user (disabledBy_id)');
        $this->addSql('UPDATE user SET enabled = true');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6493F1E31AA');
        $this->addSql('DROP INDEX IDX_8D93D6493F1E31AA ON user');
        $this->addSql('ALTER TABLE user DROP disabledAt, DROP disabledBy_id');
    }
}
