<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260619151032 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE branch ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE branch ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE branch ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE branch ADD created_by INT DEFAULT NULL');
        $this->addSql('ALTER TABLE branch ADD updated_by INT DEFAULT NULL');
        $this->addSql('DROP INDEX uniq_identifier_email');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email) WHERE deleted_at IS NULL');
        $this->addSql('ALTER TABLE warehouse ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE warehouse ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE warehouse ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE warehouse ADD created_by INT DEFAULT NULL');
        $this->addSql('ALTER TABLE warehouse ADD updated_by INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE branch DROP created_at');
        $this->addSql('ALTER TABLE branch DROP updated_at');
        $this->addSql('ALTER TABLE branch DROP deleted_at');
        $this->addSql('ALTER TABLE branch DROP created_by');
        $this->addSql('ALTER TABLE branch DROP updated_by');
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_EMAIL');
        $this->addSql('CREATE UNIQUE INDEX uniq_identifier_email ON "user" (email) WHERE (deleted_at IS NULL)');
        $this->addSql('ALTER TABLE warehouse DROP created_at');
        $this->addSql('ALTER TABLE warehouse DROP updated_at');
        $this->addSql('ALTER TABLE warehouse DROP deleted_at');
        $this->addSql('ALTER TABLE warehouse DROP created_by');
        $this->addSql('ALTER TABLE warehouse DROP updated_by');
    }
}
