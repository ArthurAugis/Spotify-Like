<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250930112738 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE track ADD uploaded_by_id INT NULL');
        $this->addSql('UPDATE track SET uploaded_by_id = (SELECT id FROM `user` ORDER BY id LIMIT 1) WHERE uploaded_by_id IS NULL');
        $this->addSql('ALTER TABLE track MODIFY uploaded_by_id INT NOT NULL');
        $this->addSql('ALTER TABLE track ADD CONSTRAINT FK_D6E3F8A6A2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_D6E3F8A6A2B28FE8 ON track (uploaded_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE track DROP FOREIGN KEY FK_D6E3F8A6A2B28FE8');
        $this->addSql('DROP INDEX IDX_D6E3F8A6A2B28FE8 ON track');
        $this->addSql('ALTER TABLE track DROP uploaded_by_id');
    }
}
