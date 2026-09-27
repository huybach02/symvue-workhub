<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260927100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Thêm cột branch_id vào bảng dining_table để quản lý bàn ăn theo chi nhánh';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dining_table ADD branch_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE dining_table ADD CONSTRAINT FK_D25EBCE0DCD69D0F FOREIGN KEY (branch_id) REFERENCES branch (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_D25EBCE0DCD69D0F ON dining_table (branch_id)');
        // Gán các bàn ăn hiện có vào chi nhánh đầu tiên nếu có
        $this->addSql('UPDATE dining_table SET branch_id = (SELECT id FROM branch ORDER BY id ASC LIMIT 1) WHERE branch_id IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dining_table DROP CONSTRAINT FK_D25EBCE0DCD69D0F');
        $this->addSql('DROP INDEX IDX_D25EBCE0DCD69D0F');
        $this->addSql('ALTER TABLE dining_table DROP branch_id');
    }
}
