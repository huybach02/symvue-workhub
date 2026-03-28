<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260328074326 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE department DROP CONSTRAINT fk_cd1de18a6a86e473');
        $this->addSql('DROP INDEX idx_cd1de18a6a86e473');
        $this->addSql('ALTER TABLE department ADD ghi_chu TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE department ADD quan_ly_bo_phan JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE department DROP quan_ly_bo_phan_id');
        $this->addSql('DROP INDEX uniq_identifier_email');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email) WHERE deleted_at IS NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE department ADD quan_ly_bo_phan_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE department DROP ghi_chu');
        $this->addSql('ALTER TABLE department DROP quan_ly_bo_phan');
        $this->addSql('ALTER TABLE department ADD CONSTRAINT fk_cd1de18a6a86e473 FOREIGN KEY (quan_ly_bo_phan_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_cd1de18a6a86e473 ON department (quan_ly_bo_phan_id)');
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_EMAIL');
        $this->addSql('CREATE UNIQUE INDEX uniq_identifier_email ON "user" (email) WHERE (deleted_at IS NULL)');
    }
}
