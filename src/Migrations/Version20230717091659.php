<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230717091659 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE beneficiaire CHANGE activationSmsCode relayInvitationSmsCode VARCHAR(255) DEFAULT NULL, CHANGE activationSmsCodeLastSend relayInvitationSmsCodeSendAt DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE beneficiaire CHANGE relayInvitationSmsCode activationSmsCode VARCHAR(255) DEFAULT NULL, CHANGE relayInvitationSmsCodeSendAt activationSmsCodeLastSend DATETIME DEFAULT NULL');
    }
}
