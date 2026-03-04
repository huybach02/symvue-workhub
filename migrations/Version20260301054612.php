<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260301054612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_identifier_email');
        $this->addSql('ALTER TABLE "user" ADD ma_nhan_vien VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD cmnd VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD ngay_cap_cmnd VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD noi_cap_cmnd VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD ngay_vao_lam VARCHAR(20) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6499D6C73BF ON "user" (ma_nhan_vien)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email) WHERE deleted_at IS NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_8D93D6499D6C73BF');
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_EMAIL');
        $this->addSql('ALTER TABLE "user" DROP ma_nhan_vien');
        $this->addSql('ALTER TABLE "user" DROP cmnd');
        $this->addSql('ALTER TABLE "user" DROP ngay_cap_cmnd');
        $this->addSql('ALTER TABLE "user" DROP noi_cap_cmnd');
        $this->addSql('ALTER TABLE "user" DROP ngay_vao_lam');
        $this->addSql('CREATE UNIQUE INDEX uniq_identifier_email ON "user" (email) WHERE (deleted_at IS NULL)');
    }
}
