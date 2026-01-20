<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260120145855 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" ADD name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD phone VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD gender VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD province_id VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD district_id VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD ward_id VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD birthday DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD description VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD email_verified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD status INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD is_ngoai_gio INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD ma_vai_tro VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD remember_token VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER roles DROP NOT NULL');
        $this->addSql('COMMENT ON COLUMN "user".status IS \'1: active, 0: inactive\'');
        $this->addSql('COMMENT ON COLUMN "user".is_ngoai_gio IS \'0: cho phép ngoại giờ, 1: không cho phép ngoại giờ\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" DROP name');
        $this->addSql('ALTER TABLE "user" DROP phone');
        $this->addSql('ALTER TABLE "user" DROP gender');
        $this->addSql('ALTER TABLE "user" DROP province_id');
        $this->addSql('ALTER TABLE "user" DROP district_id');
        $this->addSql('ALTER TABLE "user" DROP ward_id');
        $this->addSql('ALTER TABLE "user" DROP address');
        $this->addSql('ALTER TABLE "user" DROP birthday');
        $this->addSql('ALTER TABLE "user" DROP image');
        $this->addSql('ALTER TABLE "user" DROP description');
        $this->addSql('ALTER TABLE "user" DROP email_verified_at');
        $this->addSql('ALTER TABLE "user" DROP status');
        $this->addSql('ALTER TABLE "user" DROP is_ngoai_gio');
        $this->addSql('ALTER TABLE "user" DROP ma_vai_tro');
        $this->addSql('ALTER TABLE "user" DROP remember_token');
        $this->addSql('ALTER TABLE "user" DROP created_at');
        $this->addSql('ALTER TABLE "user" DROP updated_at');
        $this->addSql('ALTER TABLE "user" ALTER roles SET NOT NULL');
    }
}
