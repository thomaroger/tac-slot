<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260305103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_at field to slot';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE slot ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\''
        );
        $this->addSql('UPDATE slot SET created_at = NOW() WHERE created_at IS NULL');
        $this->addSql('ALTER TABLE slot MODIFY created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE slot DROP created_at');
    }
}
