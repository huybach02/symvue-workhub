<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260404070426 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_identifier_email');
        $this->addSql('ALTER TABLE "user" DROP bo_phan_id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email) WHERE deleted_at IS NULL');
        $this->addSql('ALTER TABLE user_permission ADD start_temp INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_permission ADD end_temp INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_position ADD start_temp INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_position ADD end_temp INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_position ALTER probation_from DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_EMAIL');
        $this->addSql('ALTER TABLE "user" ADD bo_phan_id INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_identifier_email ON "user" (email) WHERE (deleted_at IS NULL)');
        $this->addSql('ALTER TABLE user_permission DROP start_temp');
        $this->addSql('ALTER TABLE user_permission DROP end_temp');
        $this->addSql('ALTER TABLE user_position DROP start_temp');
        $this->addSql('ALTER TABLE user_position DROP end_temp');
        $this->addSql('ALTER TABLE user_position ALTER probation_from SET NOT NULL');
    }
}
