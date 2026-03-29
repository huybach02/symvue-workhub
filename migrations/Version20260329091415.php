<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260329091415 extends AbstractMigration
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
        $this->addSql('ALTER TABLE user_position ADD status INT DEFAULT 1');
        $this->addSql('ALTER TABLE user_position ADD is_primary INT DEFAULT 0');
        $this->addSql('COMMENT ON COLUMN user_position.status IS \'1: active, 0: inactive\'');
        $this->addSql('COMMENT ON COLUMN user_position.is_primary IS \'1: yes, 0: no\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_EMAIL');
        $this->addSql('CREATE UNIQUE INDEX uniq_identifier_email ON "user" (email) WHERE (deleted_at IS NULL)');
        $this->addSql('ALTER TABLE user_position DROP status');
        $this->addSql('ALTER TABLE user_position DROP is_primary');
    }
}
