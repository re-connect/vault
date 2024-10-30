<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241030105437 extends AbstractMigration
{
    #[\Override]
    public function getDescription(): string
    {
        return '';
    }

    #[\Override]
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE accesstoken RENAME INDEX idx_f4cbb726a76ed395 TO IDX_B39617F5A76ED395');
        $this->addSql('ALTER TABLE accesstoken RENAME INDEX idx_f4cbb72619eb6921 TO IDX_B39617F519EB6921');
        $this->addSql('ALTER TABLE accesstoken RENAME INDEX uniq_f4cbb7265f37a13b TO UNIQ_B39617F55F37A13B');
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE accesstoken RENAME INDEX idx_b39617f5a76ed395 TO IDX_F4CBB726A76ED395');
        $this->addSql('ALTER TABLE accesstoken RENAME INDEX uniq_b39617f55f37a13b TO UNIQ_F4CBB7265F37A13B');
        $this->addSql('ALTER TABLE accesstoken RENAME INDEX idx_b39617f519eb6921 TO IDX_F4CBB72619EB6921');
    }
}
