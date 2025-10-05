<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251005120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create recommendation table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE recommendation (
            id INT AUTO_INCREMENT NOT NULL, 
            user_id INT NOT NULL, 
            recommended_track_id INT NOT NULL, 
            reason VARCHAR(50) NOT NULL, 
            score NUMERIC(3, 2) NOT NULL, 
            created_at DATETIME NOT NULL, 
            viewed TINYINT(1) DEFAULT 0 NOT NULL, 
            liked TINYINT(1) DEFAULT 0 NOT NULL, 
            dismissed TINYINT(1) DEFAULT 0 NOT NULL, 
            INDEX IDX_433224D2A76ED395 (user_id), 
            INDEX IDX_433224D2B1E7E4E5 (recommended_track_id), 
            INDEX idx_recommendation_created (created_at), 
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        $this->addSql('ALTER TABLE recommendation ADD CONSTRAINT FK_433224D2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE recommendation ADD CONSTRAINT FK_433224D2B1E7E4E5 FOREIGN KEY (recommended_track_id) REFERENCES track (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE recommendation DROP FOREIGN KEY FK_433224D2A76ED395');
        $this->addSql('ALTER TABLE recommendation DROP FOREIGN KEY FK_433224D2B1E7E4E5');
        $this->addSql('DROP TABLE recommendation');
    }
}