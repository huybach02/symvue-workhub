<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260419103228 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_identifier_email');
        $this->addSql('ALTER TABLE "user" ALTER hinh_thuc_lam_viec TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE "user" ALTER hinh_thuc_lam_viec DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ALTER hinh_thuc_lam_viec DROP NOT NULL');
        $this->addSql('COMMENT ON COLUMN "user".hinh_thuc_lam_viec IS \'\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email) WHERE deleted_at IS NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_EMAIL');
        $this->addSql('ALTER TABLE "user" ALTER hinh_thuc_lam_viec TYPE INT');
        $this->addSql('ALTER TABLE "user" ALTER hinh_thuc_lam_viec SET DEFAULT 1');
        $this->addSql('ALTER TABLE "user" ALTER hinh_thuc_lam_viec SET NOT NULL');
        $this->addSql('COMMENT ON COLUMN "user".hinh_thuc_lam_viec IS \'1: full time, 2: part time\'');
        $this->addSql('CREATE UNIQUE INDEX uniq_identifier_email ON "user" (email) WHERE (deleted_at IS NULL)');
    }
}
