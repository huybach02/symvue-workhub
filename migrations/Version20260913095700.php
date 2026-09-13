<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260913095700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Chuyển cột status của sale_order từ SMALLINT sang VARCHAR(30) với các trạng thái chuỗi';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE sale_order ALTER COLUMN status TYPE VARCHAR(30) USING (
            CASE 
                WHEN status = 1 THEN 'COMPLETED'
                WHEN status = 2 THEN 'PROCESSING'
                ELSE 'CREATED'
            END
        )");
        $this->addSql("ALTER TABLE sale_order ALTER COLUMN status SET DEFAULT 'CREATED'");
        $this->addSql("COMMENT ON COLUMN sale_order.status IS 'Trạng thái đơn hàng: CREATED, PROCESSING, SHIPPED, COMPLETED'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE sale_order ALTER COLUMN status DROP DEFAULT");
        $this->addSql("ALTER TABLE sale_order ALTER COLUMN status TYPE SMALLINT USING (
            CASE 
                WHEN status = 'COMPLETED' THEN 1
                WHEN status = 'PROCESSING' THEN 2
                ELSE 0
            END
        )");
        $this->addSql("ALTER TABLE sale_order ALTER COLUMN status SET DEFAULT 1");
        $this->addSql("COMMENT ON COLUMN sale_order.status IS 'Trạng thái đơn hàng (1: Hoàn thành, 2: Đang xử lý, 0: Đã hủy)'");
    }
}
