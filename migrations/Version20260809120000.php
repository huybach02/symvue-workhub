<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260809120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add production supplement snapshot and inspection idempotency constraint';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE production_order_item ADD supplement_data JSON DEFAULT NULL');
        $this->addSql("COMMENT ON COLUMN production_order_item.supplement_data IS 'Snapshot mục tiêu sản xuất và kế hoạch bù'");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PRODUCTION_ITEM_INSPECTION_REQUEST ON production_item_inspection (production_order_item_id, client_request_uuid)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_PRODUCTION_ITEM_INSPECTION_REQUEST');
        $this->addSql('ALTER TABLE production_order_item DROP supplement_data');
    }
}
