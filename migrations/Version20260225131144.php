<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260225131144 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(
            'CREATE TABLE reservation (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, checked_in TINYINT NOT NULL, reserved_at DATETIME NOT NULL, checked_in_at DATETIME DEFAULT NULL, cancelled_at DATETIME DEFAULT NULL, slot_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_42C8495559E5119C (slot_id), INDEX IDX_42C84955A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8'
        );
        $this->addSql(
            'CREATE TABLE slot (id INT AUTO_INCREMENT NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME NOT NULL, max_places INT NOT NULL, reserved_places INT NOT NULL, is_closed TINYINT NOT NULL, requires_air_key TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8'
        );
        $this->addSql(
            'ALTER TABLE reservation ADD CONSTRAINT FK_42C8495559E5119C FOREIGN KEY (slot_id) REFERENCES slot (id)'
        );
        $this->addSql(
            'ALTER TABLE reservation ADD CONSTRAINT FK_42C84955A76ED395 FOREIGN KEY (user_id) REFERENCES adherent (id)'
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495559E5119C');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955A76ED395');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE slot');
    }
}
