<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210503122829 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD emailForgotPasswordTokenRequestedAt DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD emailForgotPasswordTokenMustBeVerifiedBefore DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD emailForgotPasswordTokenVerifiedAt DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP forgotPasswordTokenRequestedAt, DROP forgotPasswordTokenMustBeVerifiedBefore, DROP forgotPasswordTokenVerifiedAt, CHANGE forgotpasswordtoken emailForgotPasswordToken VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD forgotPasswordTokenRequestedAt DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD forgotPasswordTokenMustBeVerifiedBefore DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD forgotPasswordTokenVerifiedAt DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP emailForgotPasswordTokenRequestedAt, DROP emailForgotPasswordTokenMustBeVerifiedBefore, DROP emailForgotPasswordTokenVerifiedAt, CHANGE emailforgotpasswordtoken forgotPasswordToken VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`');
    }
}
