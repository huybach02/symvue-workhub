<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Tách actual cost khỏi planned cost trên production_order_material.
 */
final class Version20260808120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add actual_cost column to production_order_material';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE production_order_material ADD actual_cost NUMERIC(20, 4) DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN production_order_material.actual_cost IS \'Tổng cost thực tế khi xuất kho\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE production_order_material DROP actual_cost');
    }
}
