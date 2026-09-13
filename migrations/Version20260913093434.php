<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260913093434 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Thêm column recipe_snapshot vào table sale_order_item để snapshot công thức định lượng';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sale_order_item ADD recipe_snapshot JSON DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN sale_order_item.recipe_snapshot IS \'Toàn bộ dữ liệu snapshot công thức định lượng của variant tại thời điểm tạo đơn\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sale_order_item DROP recipe_snapshot');
    }
}
