<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260307092357 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message DROP CONSTRAINT fk_b6bd307f6061f7cf');
        $this->addSql('ALTER TABLE message DROP CONSTRAINT fk_b6bd307f9c1ab49a');
        $this->addSql('DROP INDEX idx_b6bd307f9c1ab49a');
        $this->addSql('DROP INDEX idx_b6bd307f6061f7cf');
        $this->addSql('ALTER TABLE message ADD sender_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE message ADD receiver_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE message DROP sender_id_id');
        $this->addSql('ALTER TABLE message DROP receive_id_id');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF624B39D FOREIGN KEY (sender_id) REFERENCES "user" (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FCD53EDB6 FOREIGN KEY (receiver_id) REFERENCES "user" (id)');
        $this->addSql('CREATE INDEX IDX_B6BD307FF624B39D ON message (sender_id)');
        $this->addSql('CREATE INDEX IDX_B6BD307FCD53EDB6 ON message (receiver_id)');
        $this->addSql('DROP INDEX uniq_identifier_email');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email) WHERE deleted_at IS NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message DROP CONSTRAINT FK_B6BD307FF624B39D');
        $this->addSql('ALTER TABLE message DROP CONSTRAINT FK_B6BD307FCD53EDB6');
        $this->addSql('DROP INDEX IDX_B6BD307FF624B39D');
        $this->addSql('DROP INDEX IDX_B6BD307FCD53EDB6');
        $this->addSql('ALTER TABLE message ADD sender_id_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE message ADD receive_id_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE message DROP sender_id');
        $this->addSql('ALTER TABLE message DROP receiver_id');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT fk_b6bd307f6061f7cf FOREIGN KEY (sender_id_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT fk_b6bd307f9c1ab49a FOREIGN KEY (receive_id_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_b6bd307f9c1ab49a ON message (receive_id_id)');
        $this->addSql('CREATE INDEX idx_b6bd307f6061f7cf ON message (sender_id_id)');
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_EMAIL');
        $this->addSql('CREATE UNIQUE INDEX uniq_identifier_email ON "user" (email) WHERE (deleted_at IS NULL)');
    }
}
