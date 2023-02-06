<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200819120406 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE accesstoken (id INT AUTO_INCREMENT NOT NULL, client_id INT NOT NULL, user_id INT DEFAULT NULL, token VARCHAR(255) NOT NULL, expiresAt INT DEFAULT NULL, scope VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_F4CBB7265F37A13B (token), INDEX IDX_F4CBB72619EB6921 (client_id), INDEX IDX_F4CBB726A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE administrateur (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, UNIQUE INDEX UNIQ_32EB52E8A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE adresse (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, ville VARCHAR(255) NOT NULL, codePostal VARCHAR(255) NOT NULL, pays VARCHAR(255) NOT NULL, lat DOUBLE PRECISION DEFAULT NULL, lng DOUBLE PRECISION DEFAULT NULL, hasBeenGeocoded TINYINT(1) DEFAULT \'0\' NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE annexe (id INT UNSIGNED AUTO_INCREMENT NOT NULL, url VARCHAR(255) NOT NULL, fichier VARCHAR(255) NOT NULL, actif TINYINT(1) DEFAULT \'1\' NOT NULL, date_ajout DATETIME NOT NULL, UNIQUE INDEX UNIQ_1BB35BA2F47645AE (url), UNIQUE INDEX UNIQ_1BB35BA29B76551F (fichier), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE association (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, categorieJuridique VARCHAR(255) DEFAULT NULL, siren VARCHAR(255) DEFAULT NULL, urlSite VARCHAR(255) DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, UNIQUE INDEX UNIQ_FD8521CCA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE authcode (id INT AUTO_INCREMENT NOT NULL, client_id INT NOT NULL, user_id INT DEFAULT NULL, token VARCHAR(255) NOT NULL, expiresAt INT DEFAULT NULL, scope VARCHAR(255) DEFAULT NULL, redirectUri LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_A8931C1F5F37A13B (token), INDEX IDX_A8931C1F19EB6921 (client_id), INDEX IDX_A8931C1FA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE beneficiaire (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, totalFileSize INT DEFAULT 0 NOT NULL, questionSecrete VARCHAR(255) DEFAULT NULL, reponseSecrete VARCHAR(255) DEFAULT NULL, dateNaissance DATE NOT NULL, lieuNaissance VARCHAR(255) DEFAULT NULL, archiveName VARCHAR(255) DEFAULT NULL, activationSmsCode VARCHAR(255) DEFAULT NULL, activationSmsCodeLastSend DATETIME DEFAULT NULL, isCreating TINYINT(1) NOT NULL, neverClickedMesDocuments TINYINT(1) DEFAULT \'1\' NOT NULL, idRosalie INT DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, creePar_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_B140D802A76ED395 (user_id), INDEX IDX_B140D802B660D3F4 (creePar_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE beneficiaire_client (distant_id INT UNSIGNED NOT NULL, beneficiaire_id INT NOT NULL, client_id INT NOT NULL, date_creation DATETIME NOT NULL, INDEX IDX_AAB81D0B5AF81F68 (beneficiaire_id), INDEX IDX_AAB81D0B19EB6921 (client_id), INDEX distant_id (distant_id), PRIMARY KEY(distant_id, beneficiaire_id, client_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE beneficiairecentre (id INT AUTO_INCREMENT NOT NULL, initiateur_id INT DEFAULT NULL, centre_id INT NOT NULL, beneficiaire_id INT NOT NULL, bValid TINYINT(1) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, INDEX IDX_6D65B3FC56D142FC (initiateur_id), INDEX IDX_6D65B3FC463CD7C3 (centre_id), INDEX IDX_6D65B3FC5AF81F68 (beneficiaire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE categorie (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE centre (id INT AUTO_INCREMENT NOT NULL, adresse_id INT DEFAULT NULL, gestionnaire_id INT NOT NULL, nom VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, siret VARCHAR(255) DEFAULT NULL, finess VARCHAR(255) DEFAULT NULL, telephone VARCHAR(255) DEFAULT NULL, budgetAnnuel VARCHAR(255) DEFAULT NULL, justificatifName VARCHAR(255) DEFAULT NULL, smsCount INT DEFAULT 0 NOT NULL, dateFinCotisation DATETIME DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, test TINYINT(1) NOT NULL, typeCentre_id INT NOT NULL, UNIQUE INDEX UNIQ_C6A0EA754DE7DC5C (adresse_id), INDEX IDX_C6A0EA756885AC1B (gestionnaire_id), INDEX IDX_C6A0EA7527F237FC (typeCentre_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE client (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) DEFAULT NULL, secret VARCHAR(255) NOT NULL, random_id VARCHAR(255) NOT NULL, redirect_uris LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', allowed_grant_types LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', dossier_nom VARCHAR(255) DEFAULT NULL, dossier_image VARCHAR(255) DEFAULT NULL, actif TINYINT(1) DEFAULT \'1\' NOT NULL, access LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE client_entity (distant_id INT UNSIGNED NOT NULL, entity_name VARCHAR(255) NOT NULL, client_id INT NOT NULL, created_at DATETIME NOT NULL, update_at DATETIME NOT NULL, discr VARCHAR(255) NOT NULL, entity_id INT DEFAULT NULL, INDEX IDX_5B8E0FDB19EB6921 (client_id), INDEX IDX_5B8E0FDB81257D5D (entity_id), PRIMARY KEY(distant_id, entity_name, client_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE consultationbeneficiaire (id INT AUTO_INCREMENT NOT NULL, membre_id INT NOT NULL, beneficiaire_id INT NOT NULL, createdAt DATETIME NOT NULL, INDEX IDX_A161C746A99F74A (membre_id), INDEX IDX_A161C745AF81F68 (beneficiaire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE consultationcentre (id INT AUTO_INCREMENT NOT NULL, centre_id INT NOT NULL, beneficiaire_id INT NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, INDEX IDX_3702E4C7463CD7C3 (centre_id), INDEX IDX_3702E4C75AF81F68 (beneficiaire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE contact (id INT AUTO_INCREMENT NOT NULL, beneficiaire_id INT NOT NULL, bPrive TINYINT(1) NOT NULL, nom VARCHAR(255) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, prenom VARCHAR(255) NOT NULL, telephone VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, commentaire LONGTEXT DEFAULT NULL, association VARCHAR(255) DEFAULT NULL, deposePar_id INT DEFAULT NULL, INDEX IDX_4C62E638F2AB781 (deposePar_id), INDEX IDX_4C62E6385AF81F68 (beneficiaire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE creator (id INT UNSIGNED AUTO_INCREMENT NOT NULL, document_id INT DEFAULT NULL, user_id INT DEFAULT NULL, note_id INT DEFAULT NULL, evenement_id INT DEFAULT NULL, contact_id INT DEFAULT NULL, discr VARCHAR(255) NOT NULL, entity_id INT DEFAULT NULL, INDEX IDX_BC06EA63C33F7837 (document_id), INDEX IDX_BC06EA63A76ED395 (user_id), INDEX IDX_BC06EA6326ED0855 (note_id), INDEX IDX_BC06EA63FD02F13 (evenement_id), INDEX IDX_BC06EA63E7A1254A (contact_id), INDEX IDX_BC06EA6381257D5D (entity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE document (id INT AUTO_INCREMENT NOT NULL, dossier_id INT DEFAULT NULL, beneficiaire_id INT NOT NULL, categorie_id INT DEFAULT NULL, bPrive TINYINT(1) NOT NULL, nom VARCHAR(255) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, dateEmission DATETIME DEFAULT NULL, extension VARCHAR(10) NOT NULL, taille INT NOT NULL, objectKey VARCHAR(255) DEFAULT NULL, thumbnailKey VARCHAR(255) DEFAULT NULL, deletedAt DATETIME DEFAULT NULL, deposePar_id INT DEFAULT NULL, typeDocument_id INT DEFAULT NULL, INDEX IDX_D8698A76F2AB781 (deposePar_id), INDEX IDX_D8698A76611C0C56 (dossier_id), INDEX IDX_D8698A765AF81F68 (beneficiaire_id), INDEX IDX_D8698A763BEBD1BD (typeDocument_id), INDEX IDX_D8698A76BCF5E72D (categorie_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE dossier (id INT AUTO_INCREMENT NOT NULL, beneficiaire_id INT NOT NULL, bPrive TINYINT(1) NOT NULL, nom VARCHAR(255) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, deposePar_id INT DEFAULT NULL, INDEX IDX_3D48E037F2AB781 (deposePar_id), INDEX IDX_3D48E0375AF81F68 (beneficiaire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE evenement (id INT AUTO_INCREMENT NOT NULL, beneficiaire_id INT NOT NULL, membre_id INT DEFAULT NULL, bPrive TINYINT(1) NOT NULL, nom VARCHAR(255) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, date DATETIME NOT NULL, lieu VARCHAR(255) DEFAULT NULL, commentaire LONGTEXT DEFAULT NULL, heureRappel INT DEFAULT NULL, bEnvoye TINYINT(1) NOT NULL, typeRappels LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', archive TINYINT(1) DEFAULT \'0\' NOT NULL, deposePar_id INT DEFAULT NULL, INDEX IDX_B26681EF2AB781 (deposePar_id), INDEX IDX_B26681E5AF81F68 (beneficiaire_id), INDEX IDX_B26681E6A99F74A (membre_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE gestionnaire (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, association_id INT NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, UNIQUE INDEX UNIQ_F4461B20A76ED395 (user_id), INDEX IDX_F4461B20EFB9C8A5 (association_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE lexik_trans_unit (id INT AUTO_INCREMENT NOT NULL, key_name VARCHAR(255) NOT NULL, domain VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX key_domain_idx (key_name, domain), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE lexik_trans_unit_translations (id INT AUTO_INCREMENT NOT NULL, file_id INT DEFAULT NULL, trans_unit_id INT DEFAULT NULL, locale VARCHAR(10) NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, modified_manually TINYINT(1) NOT NULL, INDEX IDX_B0AA394493CB796C (file_id), INDEX IDX_B0AA3944C3C583C9 (trans_unit_id), UNIQUE INDEX trans_unit_locale_idx (trans_unit_id, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE lexik_translation_file (id INT AUTO_INCREMENT NOT NULL, domain VARCHAR(255) NOT NULL, locale VARCHAR(10) NOT NULL, extention VARCHAR(10) NOT NULL, path VARCHAR(255) NOT NULL, hash VARCHAR(255) NOT NULL, UNIQUE INDEX hash_idx (hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE membre (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, activationSmsCode VARCHAR(255) DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, UNIQUE INDEX UNIQ_F6B4FB29A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE membrecentre (id INT AUTO_INCREMENT NOT NULL, initiateur_id INT DEFAULT NULL, centre_id INT NOT NULL, membre_id INT NOT NULL, bValid TINYINT(1) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, droits LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', INDEX IDX_FABE096856D142FC (initiateur_id), INDEX IDX_FABE0968463CD7C3 (centre_id), INDEX IDX_FABE09686A99F74A (membre_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE note (id INT AUTO_INCREMENT NOT NULL, beneficiaire_id INT NOT NULL, bPrive TINYINT(1) NOT NULL, nom VARCHAR(255) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, contenu LONGTEXT NOT NULL, deposePar_id INT DEFAULT NULL, INDEX IDX_CFBDFA14F2AB781 (deposePar_id), INDEX IDX_CFBDFA145AF81F68 (beneficiaire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE partenaire (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, imageName VARCHAR(255) NOT NULL, link VARCHAR(255) DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE presse (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, titre VARCHAR(255) NOT NULL, imageName VARCHAR(255) DEFAULT NULL, link VARCHAR(255) DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE prixcentre (id INT AUTO_INCREMENT NOT NULL, budget VARCHAR(255) NOT NULL, prix DOUBLE PRECISION NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rappel (id INT UNSIGNED AUTO_INCREMENT NOT NULL, evenement_id INT DEFAULT NULL, date DATETIME NOT NULL, bEnvoye TINYINT(1) NOT NULL, types LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', archive TINYINT(1) DEFAULT \'0\' NOT NULL, INDEX IDX_303A29C9FD02F13 (evenement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE refreshtoken (id INT AUTO_INCREMENT NOT NULL, client_id INT NOT NULL, user_id INT DEFAULT NULL, token VARCHAR(255) NOT NULL, expiresAt INT DEFAULT NULL, scope VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_A8B2C3625F37A13B (token), INDEX IDX_A8B2C36219EB6921 (client_id), INDEX IDX_A8B2C362A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sms (id INT AUTO_INCREMENT NOT NULL, rappel_id INT UNSIGNED DEFAULT NULL, evenement_id INT DEFAULT NULL, centre_id INT DEFAULT NULL, beneficiaire_id INT NOT NULL, dest VARCHAR(255) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, UNIQUE INDEX UNIQ_B0A93A777A752E96 (rappel_id), UNIQUE INDEX UNIQ_B0A93A77FD02F13 (evenement_id), INDEX IDX_B0A93A77463CD7C3 (centre_id), INDEX IDX_B0A93A775AF81F68 (beneficiaire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE statistiquecentre (id INT AUTO_INCREMENT NOT NULL, centre_id INT NOT NULL, nom VARCHAR(255) NOT NULL, donnees LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', INDEX IDX_E9AFD40E463CD7C3 (centre_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE typecentre (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE typedocument (id INT AUTO_INCREMENT NOT NULL, categorie_id INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, dureeDeVie INT DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, INDEX IDX_840004FEBCF5E72D (categorie_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, adresse_id INT DEFAULT NULL, username VARCHAR(180) NOT NULL, username_canonical VARCHAR(180) NOT NULL, enabled TINYINT(1) NOT NULL, salt VARCHAR(255) DEFAULT NULL, password VARCHAR(255) NOT NULL, last_login DATETIME DEFAULT NULL, confirmation_token VARCHAR(180) DEFAULT NULL, password_requested_at DATETIME DEFAULT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', avatar VARCHAR(255) DEFAULT NULL, prenom VARCHAR(255) DEFAULT NULL, nom VARCHAR(255) DEFAULT NULL, telephone VARCHAR(255) DEFAULT NULL, telephoneFixe VARCHAR(255) DEFAULT NULL, firstVisit TINYINT(1) DEFAULT \'1\' NOT NULL, bFirstMobileConnexion TINYINT(1) DEFAULT \'0\' NOT NULL, bActif TINYINT(1) NOT NULL, isTestUser TINYINT(1) NOT NULL, typeUser VARCHAR(255) NOT NULL, privateKey VARCHAR(255) NOT NULL, lastIp VARCHAR(20) NOT NULL, smsPasswordResetCode VARCHAR(125) DEFAULT NULL, smsPasswordResetDate DATETIME DEFAULT NULL, derniereConnexionAt DATETIME DEFAULT NULL, createdAt DATETIME NOT NULL, updatedAt DATETIME NOT NULL, test TINYINT(1) NOT NULL, email VARCHAR(255) DEFAULT NULL, emailCanonical VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D64992FC23A8 (username_canonical), UNIQUE INDEX UNIQ_8D93D649C05FB297 (confirmation_token), UNIQUE INDEX UNIQ_8D93D6494DE7DC5C (adresse_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE accesstoken ADD CONSTRAINT FK_F4CBB72619EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE accesstoken ADD CONSTRAINT FK_F4CBB726A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE administrateur ADD CONSTRAINT FK_32EB52E8A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE association ADD CONSTRAINT FK_FD8521CCA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE authcode ADD CONSTRAINT FK_A8931C1F19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE authcode ADD CONSTRAINT FK_A8931C1FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE beneficiaire ADD CONSTRAINT FK_B140D802A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE beneficiaire ADD CONSTRAINT FK_B140D802B660D3F4 FOREIGN KEY (creePar_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE beneficiaire_client ADD CONSTRAINT FK_AAB81D0B5AF81F68 FOREIGN KEY (beneficiaire_id) REFERENCES beneficiaire (id)');
        $this->addSql('ALTER TABLE beneficiaire_client ADD CONSTRAINT FK_AAB81D0B19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE beneficiairecentre ADD CONSTRAINT FK_6D65B3FC56D142FC FOREIGN KEY (initiateur_id) REFERENCES membre (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE beneficiairecentre ADD CONSTRAINT FK_6D65B3FC463CD7C3 FOREIGN KEY (centre_id) REFERENCES centre (id)');
        $this->addSql('ALTER TABLE beneficiairecentre ADD CONSTRAINT FK_6D65B3FC5AF81F68 FOREIGN KEY (beneficiaire_id) REFERENCES beneficiaire (id)');
        $this->addSql('ALTER TABLE centre ADD CONSTRAINT FK_C6A0EA754DE7DC5C FOREIGN KEY (adresse_id) REFERENCES adresse (id)');
        $this->addSql('ALTER TABLE centre ADD CONSTRAINT FK_C6A0EA756885AC1B FOREIGN KEY (gestionnaire_id) REFERENCES gestionnaire (id)');
        $this->addSql('ALTER TABLE centre ADD CONSTRAINT FK_C6A0EA7527F237FC FOREIGN KEY (typeCentre_id) REFERENCES typecentre (id)');
        $this->addSql('ALTER TABLE client_entity ADD CONSTRAINT FK_5B8E0FDB19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE consultationbeneficiaire ADD CONSTRAINT FK_A161C746A99F74A FOREIGN KEY (membre_id) REFERENCES membre (id)');
        $this->addSql('ALTER TABLE consultationbeneficiaire ADD CONSTRAINT FK_A161C745AF81F68 FOREIGN KEY (beneficiaire_id) REFERENCES beneficiaire (id)');
        $this->addSql('ALTER TABLE consultationcentre ADD CONSTRAINT FK_3702E4C7463CD7C3 FOREIGN KEY (centre_id) REFERENCES centre (id)');
        $this->addSql('ALTER TABLE consultationcentre ADD CONSTRAINT FK_3702E4C75AF81F68 FOREIGN KEY (beneficiaire_id) REFERENCES beneficiaire (id)');
        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E638F2AB781 FOREIGN KEY (deposePar_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E6385AF81F68 FOREIGN KEY (beneficiaire_id) REFERENCES beneficiaire (id)');
        $this->addSql('ALTER TABLE creator ADD CONSTRAINT FK_BC06EA63C33F7837 FOREIGN KEY (document_id) REFERENCES document (id)');
        $this->addSql('ALTER TABLE creator ADD CONSTRAINT FK_BC06EA63A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE creator ADD CONSTRAINT FK_BC06EA6326ED0855 FOREIGN KEY (note_id) REFERENCES note (id)');
        $this->addSql('ALTER TABLE creator ADD CONSTRAINT FK_BC06EA63FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id)');
        $this->addSql('ALTER TABLE creator ADD CONSTRAINT FK_BC06EA63E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76F2AB781 FOREIGN KEY (deposePar_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A765AF81F68 FOREIGN KEY (beneficiaire_id) REFERENCES beneficiaire (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A763BEBD1BD FOREIGN KEY (typeDocument_id) REFERENCES typedocument (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id)');
        $this->addSql('ALTER TABLE dossier ADD CONSTRAINT FK_3D48E037F2AB781 FOREIGN KEY (deposePar_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE dossier ADD CONSTRAINT FK_3D48E0375AF81F68 FOREIGN KEY (beneficiaire_id) REFERENCES beneficiaire (id)');
        $this->addSql('ALTER TABLE evenement ADD CONSTRAINT FK_B26681EF2AB781 FOREIGN KEY (deposePar_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE evenement ADD CONSTRAINT FK_B26681E5AF81F68 FOREIGN KEY (beneficiaire_id) REFERENCES beneficiaire (id)');
        $this->addSql('ALTER TABLE evenement ADD CONSTRAINT FK_B26681E6A99F74A FOREIGN KEY (membre_id) REFERENCES membre (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE gestionnaire ADD CONSTRAINT FK_F4461B20A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE gestionnaire ADD CONSTRAINT FK_F4461B20EFB9C8A5 FOREIGN KEY (association_id) REFERENCES association (id)');
        $this->addSql('ALTER TABLE lexik_trans_unit_translations ADD CONSTRAINT FK_B0AA394493CB796C FOREIGN KEY (file_id) REFERENCES lexik_translation_file (id)');
        $this->addSql('ALTER TABLE lexik_trans_unit_translations ADD CONSTRAINT FK_B0AA3944C3C583C9 FOREIGN KEY (trans_unit_id) REFERENCES lexik_trans_unit (id)');
        $this->addSql('ALTER TABLE membre ADD CONSTRAINT FK_F6B4FB29A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE membrecentre ADD CONSTRAINT FK_FABE096856D142FC FOREIGN KEY (initiateur_id) REFERENCES membre (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE membrecentre ADD CONSTRAINT FK_FABE0968463CD7C3 FOREIGN KEY (centre_id) REFERENCES centre (id)');
        $this->addSql('ALTER TABLE membrecentre ADD CONSTRAINT FK_FABE09686A99F74A FOREIGN KEY (membre_id) REFERENCES membre (id)');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14F2AB781 FOREIGN KEY (deposePar_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA145AF81F68 FOREIGN KEY (beneficiaire_id) REFERENCES beneficiaire (id)');
        $this->addSql('ALTER TABLE rappel ADD CONSTRAINT FK_303A29C9FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id)');
        $this->addSql('ALTER TABLE refreshtoken ADD CONSTRAINT FK_A8B2C36219EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE refreshtoken ADD CONSTRAINT FK_A8B2C362A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE sms ADD CONSTRAINT FK_B0A93A777A752E96 FOREIGN KEY (rappel_id) REFERENCES rappel (id)');
        $this->addSql('ALTER TABLE sms ADD CONSTRAINT FK_B0A93A77FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id)');
        $this->addSql('ALTER TABLE sms ADD CONSTRAINT FK_B0A93A77463CD7C3 FOREIGN KEY (centre_id) REFERENCES centre (id)');
        $this->addSql('ALTER TABLE sms ADD CONSTRAINT FK_B0A93A775AF81F68 FOREIGN KEY (beneficiaire_id) REFERENCES beneficiaire (id)');
        $this->addSql('ALTER TABLE statistiquecentre ADD CONSTRAINT FK_E9AFD40E463CD7C3 FOREIGN KEY (centre_id) REFERENCES centre (id)');
        $this->addSql('ALTER TABLE typedocument ADD CONSTRAINT FK_840004FEBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6494DE7DC5C FOREIGN KEY (adresse_id) REFERENCES adresse (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE centre DROP FOREIGN KEY FK_C6A0EA754DE7DC5C');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6494DE7DC5C');
        $this->addSql('ALTER TABLE gestionnaire DROP FOREIGN KEY FK_F4461B20EFB9C8A5');
        $this->addSql('ALTER TABLE beneficiaire_client DROP FOREIGN KEY FK_AAB81D0B5AF81F68');
        $this->addSql('ALTER TABLE beneficiairecentre DROP FOREIGN KEY FK_6D65B3FC5AF81F68');
        $this->addSql('ALTER TABLE consultationbeneficiaire DROP FOREIGN KEY FK_A161C745AF81F68');
        $this->addSql('ALTER TABLE consultationcentre DROP FOREIGN KEY FK_3702E4C75AF81F68');
        $this->addSql('ALTER TABLE contact DROP FOREIGN KEY FK_4C62E6385AF81F68');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A765AF81F68');
        $this->addSql('ALTER TABLE dossier DROP FOREIGN KEY FK_3D48E0375AF81F68');
        $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY FK_B26681E5AF81F68');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA145AF81F68');
        $this->addSql('ALTER TABLE sms DROP FOREIGN KEY FK_B0A93A775AF81F68');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A76BCF5E72D');
        $this->addSql('ALTER TABLE typedocument DROP FOREIGN KEY FK_840004FEBCF5E72D');
        $this->addSql('ALTER TABLE beneficiairecentre DROP FOREIGN KEY FK_6D65B3FC463CD7C3');
        $this->addSql('ALTER TABLE consultationcentre DROP FOREIGN KEY FK_3702E4C7463CD7C3');
        $this->addSql('ALTER TABLE membrecentre DROP FOREIGN KEY FK_FABE0968463CD7C3');
        $this->addSql('ALTER TABLE sms DROP FOREIGN KEY FK_B0A93A77463CD7C3');
        $this->addSql('ALTER TABLE statistiquecentre DROP FOREIGN KEY FK_E9AFD40E463CD7C3');
        $this->addSql('ALTER TABLE accesstoken DROP FOREIGN KEY FK_F4CBB72619EB6921');
        $this->addSql('ALTER TABLE authcode DROP FOREIGN KEY FK_A8931C1F19EB6921');
        $this->addSql('ALTER TABLE beneficiaire_client DROP FOREIGN KEY FK_AAB81D0B19EB6921');
        $this->addSql('ALTER TABLE client_entity DROP FOREIGN KEY FK_5B8E0FDB19EB6921');
        $this->addSql('ALTER TABLE refreshtoken DROP FOREIGN KEY FK_A8B2C36219EB6921');
        $this->addSql('ALTER TABLE creator DROP FOREIGN KEY FK_BC06EA63E7A1254A');
        $this->addSql('ALTER TABLE creator DROP FOREIGN KEY FK_BC06EA63C33F7837');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A76611C0C56');
        $this->addSql('ALTER TABLE creator DROP FOREIGN KEY FK_BC06EA63FD02F13');
        $this->addSql('ALTER TABLE rappel DROP FOREIGN KEY FK_303A29C9FD02F13');
        $this->addSql('ALTER TABLE sms DROP FOREIGN KEY FK_B0A93A77FD02F13');
        $this->addSql('ALTER TABLE centre DROP FOREIGN KEY FK_C6A0EA756885AC1B');
        $this->addSql('ALTER TABLE lexik_trans_unit_translations DROP FOREIGN KEY FK_B0AA3944C3C583C9');
        $this->addSql('ALTER TABLE lexik_trans_unit_translations DROP FOREIGN KEY FK_B0AA394493CB796C');
        $this->addSql('ALTER TABLE beneficiairecentre DROP FOREIGN KEY FK_6D65B3FC56D142FC');
        $this->addSql('ALTER TABLE consultationbeneficiaire DROP FOREIGN KEY FK_A161C746A99F74A');
        $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY FK_B26681E6A99F74A');
        $this->addSql('ALTER TABLE membrecentre DROP FOREIGN KEY FK_FABE096856D142FC');
        $this->addSql('ALTER TABLE membrecentre DROP FOREIGN KEY FK_FABE09686A99F74A');
        $this->addSql('ALTER TABLE creator DROP FOREIGN KEY FK_BC06EA6326ED0855');
        $this->addSql('ALTER TABLE sms DROP FOREIGN KEY FK_B0A93A777A752E96');
        $this->addSql('ALTER TABLE centre DROP FOREIGN KEY FK_C6A0EA7527F237FC');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A763BEBD1BD');
        $this->addSql('ALTER TABLE accesstoken DROP FOREIGN KEY FK_F4CBB726A76ED395');
        $this->addSql('ALTER TABLE administrateur DROP FOREIGN KEY FK_32EB52E8A76ED395');
        $this->addSql('ALTER TABLE association DROP FOREIGN KEY FK_FD8521CCA76ED395');
        $this->addSql('ALTER TABLE authcode DROP FOREIGN KEY FK_A8931C1FA76ED395');
        $this->addSql('ALTER TABLE beneficiaire DROP FOREIGN KEY FK_B140D802A76ED395');
        $this->addSql('ALTER TABLE beneficiaire DROP FOREIGN KEY FK_B140D802B660D3F4');
        $this->addSql('ALTER TABLE contact DROP FOREIGN KEY FK_4C62E638F2AB781');
        $this->addSql('ALTER TABLE creator DROP FOREIGN KEY FK_BC06EA63A76ED395');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A76F2AB781');
        $this->addSql('ALTER TABLE dossier DROP FOREIGN KEY FK_3D48E037F2AB781');
        $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY FK_B26681EF2AB781');
        $this->addSql('ALTER TABLE gestionnaire DROP FOREIGN KEY FK_F4461B20A76ED395');
        $this->addSql('ALTER TABLE membre DROP FOREIGN KEY FK_F6B4FB29A76ED395');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14F2AB781');
        $this->addSql('ALTER TABLE refreshtoken DROP FOREIGN KEY FK_A8B2C362A76ED395');
        $this->addSql('DROP TABLE accesstoken');
        $this->addSql('DROP TABLE administrateur');
        $this->addSql('DROP TABLE adresse');
        $this->addSql('DROP TABLE annexe');
        $this->addSql('DROP TABLE association');
        $this->addSql('DROP TABLE authcode');
        $this->addSql('DROP TABLE beneficiaire');
        $this->addSql('DROP TABLE beneficiaire_client');
        $this->addSql('DROP TABLE beneficiairecentre');
        $this->addSql('DROP TABLE categorie');
        $this->addSql('DROP TABLE centre');
        $this->addSql('DROP TABLE client');
        $this->addSql('DROP TABLE client_entity');
        $this->addSql('DROP TABLE consultationbeneficiaire');
        $this->addSql('DROP TABLE consultationcentre');
        $this->addSql('DROP TABLE contact');
        $this->addSql('DROP TABLE creator');
        $this->addSql('DROP TABLE document');
        $this->addSql('DROP TABLE dossier');
        $this->addSql('DROP TABLE evenement');
        $this->addSql('DROP TABLE gestionnaire');
        $this->addSql('DROP TABLE lexik_trans_unit');
        $this->addSql('DROP TABLE lexik_trans_unit_translations');
        $this->addSql('DROP TABLE lexik_translation_file');
        $this->addSql('DROP TABLE membre');
        $this->addSql('DROP TABLE membrecentre');
        $this->addSql('DROP TABLE note');
        $this->addSql('DROP TABLE partenaire');
        $this->addSql('DROP TABLE presse');
        $this->addSql('DROP TABLE prixcentre');
        $this->addSql('DROP TABLE rappel');
        $this->addSql('DROP TABLE refreshtoken');
        $this->addSql('DROP TABLE sms');
        $this->addSql('DROP TABLE statistiquecentre');
        $this->addSql('DROP TABLE typecentre');
        $this->addSql('DROP TABLE typedocument');
        $this->addSql('DROP TABLE user');
    }
}
