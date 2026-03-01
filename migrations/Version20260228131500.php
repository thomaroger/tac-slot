<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260228131500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add expiration date for adherent email verification token';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE adherent ADD email_verification_token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE adherent DROP email_verification_token_expires_at');
    }
}
