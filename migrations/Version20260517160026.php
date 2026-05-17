<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260517160026 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Add status column for work_shift";
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE work_shift ADD status BOOLEAN DEFAULT TRUE NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE work_shift DROP status");
    }
}

