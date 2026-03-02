<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260225085420 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(
            'CREATE TABLE auth_code (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(10) NOT NULL, created_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, used_at DATETIME DEFAULT NULL, session_id VARCHAR(100) NOT NULL, type VARCHAR(20) NOT NULL, adherent_id INT NOT NULL, INDEX IDX_5933D02C25F06C53 (adherent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8'
        );
        $this->addSql(
            'CREATE TABLE auth_log (id INT AUTO_INCREMENT NOT NULL, event VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, ip VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL, adherent_id INT DEFAULT NULL, INDEX IDX_1DD25DB825F06C53 (adherent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8'
        );
        $this->addSql(
            'ALTER TABLE auth_code ADD CONSTRAINT FK_5933D02C25F06C53 FOREIGN KEY (adherent_id) REFERENCES adherent (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE auth_log ADD CONSTRAINT FK_1DD25DB825F06C53 FOREIGN KEY (adherent_id) REFERENCES adherent (id) ON DELETE SET NULL'
        );
        $this->addSql(
            'ALTER TABLE adherent ADD email_verified TINYINT DEFAULT 0 NOT NULL, ADD email_verification_token VARCHAR(100) DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE auth_code DROP FOREIGN KEY FK_5933D02C25F06C53');
        $this->addSql('ALTER TABLE auth_log DROP FOREIGN KEY FK_1DD25DB825F06C53');
        $this->addSql('DROP TABLE auth_code');
        $this->addSql('DROP TABLE auth_log');
        $this->addSql('ALTER TABLE adherent DROP email_verified, DROP email_verification_token');
    }
}
