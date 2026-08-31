<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260831120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link departments to branches and scope organizational codes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE department ADD branch_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE department
            SET branch_id = (
                SELECT id
                FROM branch
                WHERE code = 'MAIN_BRANCH' AND deleted_at IS NULL
                ORDER BY id
                LIMIT 1
            )
            WHERE branch_id IS NULL
        SQL);
        $this->addSql('ALTER TABLE department ALTER branch_id SET NOT NULL');
        $this->addSql('CREATE INDEX IDX_CD1DE18ADCD6CC49 ON department (branch_id)');
        $this->addSql('ALTER TABLE department ADD CONSTRAINT FK_DEPARTMENT_BRANCH FOREIGN KEY (branch_id) REFERENCES branch (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('DROP INDEX UNIQ_DEPARTMENT_MA_BO_PHAN');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DEPARTMENT_BRANCH_CODE ON department (branch_id, ma_bo_phan) WHERE deleted_at IS NULL');

        $this->addSql('DROP INDEX UNIQ_POSITION_CODE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_POSITION_DEPARTMENT_CODE ON "position" (department_id, code) WHERE deleted_at IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_POSITION_DEPARTMENT_CODE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_POSITION_CODE ON "position" (code) WHERE deleted_at IS NULL');

        $this->addSql('DROP INDEX UNIQ_DEPARTMENT_BRANCH_CODE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DEPARTMENT_MA_BO_PHAN ON department (ma_bo_phan) WHERE deleted_at IS NULL');

        $this->addSql('ALTER TABLE department DROP CONSTRAINT FK_DEPARTMENT_BRANCH');
        $this->addSql('DROP INDEX IDX_CD1DE18ADCD6CC49');
        $this->addSql('ALTER TABLE department DROP branch_id');
    }
}
