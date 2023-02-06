<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210506101107 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renamed properties';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD smsPasswordResetCodeRequestedAt DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD smsPasswordResetCodeMustBeVerifiedBefore DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP smsForgotPasswordTokenRequestedAt, DROP smsForgotPasswordTokenMustBeVerifiedBefore');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD smsForgotPasswordTokenRequestedAt DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD smsForgotPasswordTokenMustBeVerifiedBefore DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP smsPasswordResetCodeRequestedAt, DROP smsPasswordResetCodeMustBeVerifiedBefore');
    }
}
