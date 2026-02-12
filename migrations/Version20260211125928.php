<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211125928 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bo_phan ADD quan_ly_bo_phan_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE bo_phan ADD CONSTRAINT FK_39B34B9A6A86E473 FOREIGN KEY (quan_ly_bo_phan_id) REFERENCES "user" (id)');
        $this->addSql('CREATE INDEX IDX_39B34B9A6A86E473 ON bo_phan (quan_ly_bo_phan_id)');
        $this->addSql('DROP INDEX uniq_identifier_email');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email) WHERE deleted_at IS NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bo_phan DROP CONSTRAINT FK_39B34B9A6A86E473');
        $this->addSql('DROP INDEX IDX_39B34B9A6A86E473');
        $this->addSql('ALTER TABLE bo_phan DROP quan_ly_bo_phan_id');
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_EMAIL');
        $this->addSql('CREATE UNIQUE INDEX uniq_identifier_email ON "user" (email) WHERE (deleted_at IS NULL)');
    }
}
