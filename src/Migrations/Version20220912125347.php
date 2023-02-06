<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220912125347 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP password_requested_at, DROP smsPasswordResetCode, DROP smsPasswordResetDate, DROP emailForgotPasswordToken, DROP emailForgotPasswordTokenRequestedAt, DROP emailForgotPasswordTokenMustBeVerifiedBefore, DROP emailForgotPasswordTokenVerifiedAt, DROP smsForgotPasswordToken, DROP smsForgotPasswordTokenVerifiedAt, DROP smsPasswordResetCodeRequestedAt, DROP smsPasswordResetCodeMustBeVerifiedBefore');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD password_requested_at DATETIME DEFAULT NULL, ADD smsPasswordResetCode VARCHAR(125) DEFAULT NULL, ADD smsPasswordResetDate DATETIME DEFAULT NULL, ADD emailForgotPasswordToken VARCHAR(255) DEFAULT NULL, ADD emailForgotPasswordTokenRequestedAt DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD emailForgotPasswordTokenMustBeVerifiedBefore DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD emailForgotPasswordTokenVerifiedAt DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD smsForgotPasswordToken VARCHAR(255) DEFAULT NULL, ADD smsForgotPasswordTokenVerifiedAt DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD smsPasswordResetCodeRequestedAt DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD smsPasswordResetCodeMustBeVerifiedBefore DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
