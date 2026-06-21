<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260621142604 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make user employee code unique only for non-deleted users.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS UNIQ_8D93D6499D6C73BF');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_MA_NHAN_VIEN ON "user" (ma_nhan_vien) WHERE deleted_at IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS UNIQ_USER_MA_NHAN_VIEN');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6499D6C73BF ON "user" (ma_nhan_vien)');
    }
}
