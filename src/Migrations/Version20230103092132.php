<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230103092132 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE beneficiary_creation_process (id INT AUTO_INCREMENT NOT NULL, beneficiary_id INT NOT NULL, isCreating TINYINT(1) DEFAULT 0 NOT NULL, remotely TINYINT(1) DEFAULT 0 NOT NULL, UNIQUE INDEX UNIQ_5AAB764AECCAAFA0 (beneficiary_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE beneficiary_creation_process ADD CONSTRAINT FK_5AAB764AECCAAFA0 FOREIGN KEY (beneficiary_id) REFERENCES beneficiaire (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE beneficiaire ADD creationProcess_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE beneficiaire ADD CONSTRAINT FK_B140D80292D7762C FOREIGN KEY (creationProcess_id) REFERENCES beneficiary_creation_process (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B140D80292D7762C ON beneficiaire (creationProcess_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE beneficiaire DROP FOREIGN KEY FK_B140D80292D7762C');
        $this->addSql('ALTER TABLE beneficiary_creation_process DROP FOREIGN KEY FK_5AAB764AECCAAFA0');
        $this->addSql('DROP TABLE beneficiary_creation_process');
        $this->addSql('DROP INDEX UNIQ_B140D80292D7762C ON beneficiaire');
        $this->addSql('ALTER TABLE beneficiaire DROP creationProcess_id');
    }
}
