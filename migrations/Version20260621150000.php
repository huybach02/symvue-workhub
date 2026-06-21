<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260621150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change category materialized path separator from dot to slash.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE category SET path = replace(path, '.', '/') WHERE path LIKE '%.%'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE category SET path = replace(path, '/', '.') WHERE path LIKE '%/%'");
    }
}
