<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260912064653 extends AbstractMigration
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
        $this->addSql('DROP INDEX uniq_business_product_code');
        $this->addSql("INSERT INTO image (entity_type, entity_id, path, type, created_at, updated_at) SELECT 'App\\\\Entity\\\\BusinessProduct', id, image_url, 'image', NOW(), NOW() FROM business_product WHERE image_url IS NOT NULL AND image_url != ''");
        $this->addSql('ALTER TABLE business_product DROP image_url');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BUSINESS_PRODUCT_CODE ON business_product (code) WHERE deleted_at IS NULL');
        $this->addSql('DROP INDEX uniq_business_product_variant_code');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BUSINESS_PRODUCT_VARIANT_CODE ON business_product_variant (code) WHERE deleted_at IS NULL');
        $this->addSql('DROP INDEX uniq_category_slug');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CATEGORY_SLUG ON category (slug) WHERE deleted_at IS NULL');
        $this->addSql('DROP INDEX uniq_department_branch_code');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DEPARTMENT_BRANCH_CODE ON department (branch_id, ma_bo_phan) WHERE deleted_at IS NULL');
        $this->addSql('DROP INDEX uniq_merchandise_code');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_MERCHANDISE_CODE ON merchandise (code) WHERE deleted_at IS NULL');
        $this->addSql('DROP INDEX uniq_position_department_code');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_POSITION_DEPARTMENT_CODE ON "position" (department_id, code) WHERE deleted_at IS NULL');
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
        $this->addSql('DROP INDEX UNIQ_BUSINESS_PRODUCT_CODE');
        $this->addSql('ALTER TABLE business_product ADD image_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN business_product.image_url IS \'Đường dẫn ảnh\'');
        $this->addSql('CREATE UNIQUE INDEX uniq_business_product_code ON business_product (code) WHERE (deleted_at IS NULL)');
        $this->addSql('DROP INDEX UNIQ_BUSINESS_PRODUCT_VARIANT_CODE');
        $this->addSql('CREATE UNIQUE INDEX uniq_business_product_variant_code ON business_product_variant (code) WHERE (deleted_at IS NULL)');
        $this->addSql('DROP INDEX UNIQ_CATEGORY_SLUG');
        $this->addSql('CREATE UNIQUE INDEX uniq_category_slug ON category (slug) WHERE (deleted_at IS NULL)');
        $this->addSql('DROP INDEX UNIQ_DEPARTMENT_BRANCH_CODE');
        $this->addSql('CREATE UNIQUE INDEX uniq_department_branch_code ON department (branch_id, ma_bo_phan) WHERE (deleted_at IS NULL)');
        $this->addSql('DROP INDEX UNIQ_MERCHANDISE_CODE');
        $this->addSql('CREATE UNIQUE INDEX uniq_merchandise_code ON merchandise (code) WHERE (deleted_at IS NULL)');
        $this->addSql('DROP INDEX UNIQ_POSITION_DEPARTMENT_CODE');
        $this->addSql('CREATE UNIQUE INDEX uniq_position_department_code ON "position" (department_id, code) WHERE (deleted_at IS NULL)');
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
