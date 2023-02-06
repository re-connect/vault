<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211219170407 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE lexik_trans_unit_translations DROP FOREIGN KEY FK_B0AA3944C3C583C9');
        $this->addSql('ALTER TABLE lexik_trans_unit_translations DROP FOREIGN KEY FK_B0AA394493CB796C');
        $this->addSql('CREATE TABLE oauth2_access_token (identifier CHAR(80) NOT NULL, client VARCHAR(80) NOT NULL, expiry DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', userIdentifier VARCHAR(128) DEFAULT NULL, scopes TEXT DEFAULT NULL COMMENT \'(DC2Type:oauth2_scope)\', revoked TINYINT(1) NOT NULL, INDEX IDX_454D9673C7440455 (client), PRIMARY KEY(identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE oauth2_authorization_code (identifier CHAR(80) NOT NULL, client VARCHAR(80) NOT NULL, expiry DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', userIdentifier VARCHAR(128) DEFAULT NULL, scopes TEXT DEFAULT NULL COMMENT \'(DC2Type:oauth2_scope)\', revoked TINYINT(1) NOT NULL, INDEX IDX_509FEF5FC7440455 (client), PRIMARY KEY(identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE oauth2_client (identifier VARCHAR(80) NOT NULL, name VARCHAR(128) NOT NULL, secret VARCHAR(128) DEFAULT NULL, redirectUris TEXT DEFAULT NULL COMMENT \'(DC2Type:oauth2_redirect_uri)\', grants TEXT DEFAULT NULL COMMENT \'(DC2Type:oauth2_grant)\', scopes TEXT DEFAULT NULL COMMENT \'(DC2Type:oauth2_scope)\', active TINYINT(1) NOT NULL, allowPlainTextPkce TINYINT(1) DEFAULT \'0\' NOT NULL, PRIMARY KEY(identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE oauth2_refresh_token (identifier CHAR(80) NOT NULL, access_token CHAR(80) DEFAULT NULL, expiry DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', revoked TINYINT(1) NOT NULL, INDEX IDX_4DD90732B6A2DD68 (access_token), PRIMARY KEY(identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE oauth2_access_token ADD CONSTRAINT FK_454D9673C7440455 FOREIGN KEY (client) REFERENCES oauth2_client (identifier) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE oauth2_authorization_code ADD CONSTRAINT FK_509FEF5FC7440455 FOREIGN KEY (client) REFERENCES oauth2_client (identifier) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE oauth2_refresh_token ADD CONSTRAINT FK_4DD90732B6A2DD68 FOREIGN KEY (access_token) REFERENCES oauth2_access_token (identifier) ON DELETE SET NULL');
        $this->addSql('DROP TABLE lexik_trans_unit');
        $this->addSql('DROP TABLE lexik_trans_unit_translations');
        $this->addSql('DROP TABLE lexik_translation_file');
        $this->addSql('CREATE TABLE client_permission (id INT AUTO_INCREMENT NOT NULL, client_id VARCHAR(80) NOT NULL, objectClass VARCHAR(255) NOT NULL, objectId INT NOT NULL, permissions LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', INDEX IDX_701DAFEB19EB6921 (client_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE client_permission ADD CONSTRAINT FK_701DAFEB19EB6921 FOREIGN KEY (client_id) REFERENCES oauth2_client (identifier)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE client_permission');
        $this->addSql('ALTER TABLE oauth2_refresh_token DROP FOREIGN KEY FK_4DD90732B6A2DD68');
        $this->addSql('ALTER TABLE oauth2_access_token DROP FOREIGN KEY FK_454D9673C7440455');
        $this->addSql('ALTER TABLE oauth2_authorization_code DROP FOREIGN KEY FK_509FEF5FC7440455');
        $this->addSql('CREATE TABLE lexik_trans_unit (id INT AUTO_INCREMENT NOT NULL, key_name VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, domain VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX key_domain_idx (key_name, domain), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE lexik_trans_unit_translations (id INT AUTO_INCREMENT NOT NULL, file_id INT DEFAULT NULL, trans_unit_id INT DEFAULT NULL, locale VARCHAR(10) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, content LONGTEXT CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, modified_manually TINYINT(1) NOT NULL, INDEX IDX_B0AA3944C3C583C9 (trans_unit_id), UNIQUE INDEX trans_unit_locale_idx (trans_unit_id, locale), INDEX IDX_B0AA394493CB796C (file_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE lexik_translation_file (id INT AUTO_INCREMENT NOT NULL, domain VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, locale VARCHAR(10) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, extention VARCHAR(10) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, path VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, hash VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, UNIQUE INDEX hash_idx (hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE lexik_trans_unit_translations ADD CONSTRAINT FK_B0AA394493CB796C FOREIGN KEY (file_id) REFERENCES lexik_translation_file (id)');
        $this->addSql('ALTER TABLE lexik_trans_unit_translations ADD CONSTRAINT FK_B0AA3944C3C583C9 FOREIGN KEY (trans_unit_id) REFERENCES lexik_trans_unit (id)');
        $this->addSql('DROP TABLE oauth2_access_token');
        $this->addSql('DROP TABLE oauth2_authorization_code');
        $this->addSql('DROP TABLE oauth2_client');
        $this->addSql('DROP TABLE oauth2_refresh_token');
    }
}
