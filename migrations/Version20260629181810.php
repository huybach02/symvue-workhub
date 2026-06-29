<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260629181810 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_branch_code');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BRANCH_CODE ON branch (code) WHERE deleted_at IS NULL');
        $this->addSql('DROP INDEX uniq_category_slug');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CATEGORY_SLUG ON category (slug) WHERE deleted_at IS NULL');
        $this->addSql('DROP INDEX uniq_department_ma_bo_phan');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DEPARTMENT_MA_BO_PHAN ON department (ma_bo_phan) WHERE deleted_at IS NULL');
        $this->addSql('DROP INDEX uniq_merchandise_code');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_MERCHANDISE_CODE ON merchandise (code) WHERE deleted_at IS NULL');
        $this->addSql('DROP INDEX uniq_position_code');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_POSITION_CODE ON "position" (code) WHERE deleted_at IS NULL');
        $this->addSql('DROP INDEX uniq_provider_code');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PROVIDER_CODE ON provider (code) WHERE deleted_at IS NULL');
        $this->addSql('DROP INDEX uniq_unit_code');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_UNIT_CODE ON unit (code) WHERE deleted_at IS NULL');
        $this->addSql('DROP INDEX uniq_identifier_email');
        $this->addSql('DROP INDEX uniq_user_ma_nhan_vien');
        $this->addSql('DROP INDEX uniq_user_phone');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email) WHERE deleted_at IS NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_MA_NHAN_VIEN ON "user" (ma_nhan_vien) WHERE deleted_at IS NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_PHONE ON "user" (phone) WHERE deleted_at IS NULL');
        $this->addSql('DROP INDEX uniq_warehouse_code');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_WAREHOUSE_CODE ON warehouse (code) WHERE deleted_at IS NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_BRANCH_CODE');
        $this->addSql('CREATE UNIQUE INDEX uniq_branch_code ON branch (code) WHERE (deleted_at IS NULL)');
        $this->addSql('DROP INDEX UNIQ_CATEGORY_SLUG');
        $this->addSql('CREATE UNIQUE INDEX uniq_category_slug ON category (slug) WHERE (deleted_at IS NULL)');
        $this->addSql('DROP INDEX UNIQ_DEPARTMENT_MA_BO_PHAN');
        $this->addSql('CREATE UNIQUE INDEX uniq_department_ma_bo_phan ON department (ma_bo_phan) WHERE (deleted_at IS NULL)');
        $this->addSql('DROP INDEX UNIQ_MERCHANDISE_CODE');
        $this->addSql('CREATE UNIQUE INDEX uniq_merchandise_code ON merchandise (code) WHERE (deleted_at IS NULL)');
        $this->addSql('DROP INDEX UNIQ_POSITION_CODE');
        $this->addSql('CREATE UNIQUE INDEX uniq_position_code ON "position" (code) WHERE (deleted_at IS NULL)');
        $this->addSql('DROP INDEX UNIQ_PROVIDER_CODE');
        $this->addSql('CREATE UNIQUE INDEX uniq_provider_code ON provider (code) WHERE (deleted_at IS NULL)');
        $this->addSql('DROP INDEX UNIQ_UNIT_CODE');
        $this->addSql('CREATE UNIQUE INDEX uniq_unit_code ON unit (code) WHERE (deleted_at IS NULL)');
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_EMAIL');
        $this->addSql('DROP INDEX UNIQ_USER_MA_NHAN_VIEN');
        $this->addSql('DROP INDEX UNIQ_USER_PHONE');
        $this->addSql('CREATE UNIQUE INDEX uniq_identifier_email ON "user" (email) WHERE (deleted_at IS NULL)');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_ma_nhan_vien ON "user" (ma_nhan_vien) WHERE (deleted_at IS NULL)');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_phone ON "user" (phone) WHERE (deleted_at IS NULL)');
        $this->addSql('DROP INDEX UNIQ_WAREHOUSE_CODE');
        $this->addSql('CREATE UNIQUE INDEX uniq_warehouse_code ON warehouse (code) WHERE (deleted_at IS NULL)');
    }
}
