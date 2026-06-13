<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260613110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add attendance reminder fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE attendance ADD reminder_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE attendance ADD reminded_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_A338A92A58742EF6_BA48AB4E_6A093D5A ON attendance (status, reminder_at, reminded_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_A338A92A58742EF6_BA48AB4E_6A093D5A');
        $this->addSql('ALTER TABLE attendance DROP reminder_at');
        $this->addSql('ALTER TABLE attendance DROP reminded_at');
    }
}
