<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260401134404 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_identifier_email');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email) WHERE deleted_at IS NULL');
        $this->addSql('ALTER TABLE user_permission DROP is_default');
        $this->addSql('ALTER TABLE user_permission DROP is_manager');
        $this->addSql('ALTER TABLE user_permission DROP is_custom');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_EMAIL');
        $this->addSql('CREATE UNIQUE INDEX uniq_identifier_email ON "user" (email) WHERE (deleted_at IS NULL)');
        $this->addSql('ALTER TABLE user_permission ADD is_default BOOLEAN DEFAULT false');
        $this->addSql('ALTER TABLE user_permission ADD is_manager BOOLEAN DEFAULT false');
        $this->addSql('ALTER TABLE user_permission ADD is_custom BOOLEAN DEFAULT false');
    }
}
