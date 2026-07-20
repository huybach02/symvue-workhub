<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Rút client_line_uuid từ UUID xuống chuỗi 6 ký tự.
 */
final class Version20260720205755 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change stock_receipt_item_lot.client_line_uuid from UUID to VARCHAR(6)';
    }

    public function up(Schema $schema): void
    {
        // Chuyển UUID -> VARCHAR(6): lấy 6 ký tự đầu (bỏ dấu '-') và viết hoa
        $this->addSql(<<<'SQL'
            ALTER TABLE stock_receipt_item_lot
            ALTER COLUMN client_line_uuid TYPE VARCHAR(6)
            USING UPPER(LEFT(REPLACE(client_line_uuid::text, '-', ''), 6))
        SQL);
        $this->addSql(
            "COMMENT ON COLUMN stock_receipt_item_lot.client_line_uuid IS 'ID tạm 6 ký tự do UI sinh để định danh dòng lô trước khi post'",
        );
    }

    public function down(Schema $schema): void
    {
        // Không khôi phục được UUID gốc; chỉ cast về kiểu UUID nếu chuỗi còn hợp lệ
        $this->addSql(<<<'SQL'
            ALTER TABLE stock_receipt_item_lot
            ALTER COLUMN client_line_uuid TYPE UUID
            USING (
                CASE
                    WHEN client_line_uuid ~* '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$'
                        THEN client_line_uuid::uuid
                    ELSE (
                        substr(md5(client_line_uuid), 1, 8) || '-' ||
                        substr(md5(client_line_uuid), 9, 4) || '-' ||
                        substr(md5(client_line_uuid), 13, 4) || '-' ||
                        substr(md5(client_line_uuid), 17, 4) || '-' ||
                        substr(md5(client_line_uuid), 21, 12)
                    )::uuid
                END
            )
        SQL);
        $this->addSql(
            "COMMENT ON COLUMN stock_receipt_item_lot.client_line_uuid IS 'ID tạm ổn định để UI lưu/chỉnh sửa dòng trước khi post'",
        );
    }
}
