<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260609152825 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE attendance ALTER time_attendance TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE attendance ALTER work_schedule_start_time TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE attendance ALTER work_schedule_end_time TYPE VARCHAR(255)');
        $this->addSql('DROP INDEX uniq_identifier_email');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email) WHERE deleted_at IS NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE attendance ALTER time_attendance TYPE TIME(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE attendance ALTER work_schedule_start_time TYPE TIME(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE attendance ALTER work_schedule_end_time TYPE TIME(0) WITHOUT TIME ZONE');
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_EMAIL');
        $this->addSql('CREATE UNIQUE INDEX uniq_identifier_email ON "user" (email) WHERE (deleted_at IS NULL)');
    }
}
