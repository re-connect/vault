<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210503135914 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD smsForgotPasswordToken VARCHAR(255) DEFAULT NULL, ADD smsForgotPasswordTokenRequestedAt DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD smsForgotPasswordTokenMustBeVerifiedBefore DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD smsForgotPasswordTokenVerifiedAt DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP smsForgotPasswordToken, DROP smsForgotPasswordTokenRequestedAt, DROP smsForgotPasswordTokenMustBeVerifiedBefore, DROP smsForgotPasswordTokenVerifiedAt');
    }
}
