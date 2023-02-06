<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210423083514 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Added new properties related to password resetting';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD forgotPasswordToken VARCHAR(255) DEFAULT NULL, ADD forgotPasswordTokenRequestedAt DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD forgotPasswordTokenMustBeVerifiedBefore DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD forgotPasswordTokenVerifiedAt DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP forgotPasswordToken, DROP forgotPasswordTokenRequestedAt, DROP forgotPasswordTokenMustBeVerifiedBefore, DROP forgotPasswordTokenVerifiedAt');
    }
}
