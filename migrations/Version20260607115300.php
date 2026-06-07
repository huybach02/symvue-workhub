<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260607115300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE attendance ADD work_shift_assignment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE attendance ADD CONSTRAINT FK_6DE30D91655C1043 FOREIGN KEY (work_shift_assignment_id) REFERENCES work_shift_assignment (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_6DE30D91655C1043 ON attendance (work_shift_assignment_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE attendance DROP CONSTRAINT FK_6DE30D91655C1043');
        $this->addSql('DROP INDEX IDX_6DE30D91655C1043');
        $this->addSql('ALTER TABLE attendance DROP work_shift_assignment_id');
    }
}
