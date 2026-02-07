<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260207054658 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" ADD province VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD district VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD ward VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" DROP province_id');
        $this->addSql('ALTER TABLE "user" DROP district_id');
        $this->addSql('ALTER TABLE "user" DROP ward_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" ADD province_id VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD district_id VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD ward_id VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" DROP province');
        $this->addSql('ALTER TABLE "user" DROP district');
        $this->addSql('ALTER TABLE "user" DROP ward');
    }
}
