<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260401141145 extends AbstractMigration
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
        $this->addSql('ALTER TABLE user_has_custom_permission DROP CONSTRAINT fk_2228d2c39d86650f');
        $this->addSql('DROP INDEX uniq_2228d2c39d86650f');
        $this->addSql('ALTER TABLE user_has_custom_permission RENAME COLUMN user_id_id TO user_id');
        $this->addSql('ALTER TABLE user_has_custom_permission ADD CONSTRAINT FK_2228D2C3A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2228D2C3A76ED395 ON user_has_custom_permission (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_EMAIL');
        $this->addSql('CREATE UNIQUE INDEX uniq_identifier_email ON "user" (email) WHERE (deleted_at IS NULL)');
        $this->addSql('ALTER TABLE user_has_custom_permission DROP CONSTRAINT FK_2228D2C3A76ED395');
        $this->addSql('DROP INDEX UNIQ_2228D2C3A76ED395');
        $this->addSql('ALTER TABLE user_has_custom_permission RENAME COLUMN user_id TO user_id_id');
        $this->addSql('ALTER TABLE user_has_custom_permission ADD CONSTRAINT fk_2228d2c39d86650f FOREIGN KEY (user_id_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_2228d2c39d86650f ON user_has_custom_permission (user_id_id)');
    }
}
