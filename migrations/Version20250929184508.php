<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250929184508 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE playlist (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, cover_image VARCHAR(255) DEFAULT NULL, is_public TINYINT(1) DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_D782112D7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE playlist_track (playlist_id INT NOT NULL, track_id INT NOT NULL, INDEX IDX_75FFE1E56BBD148 (playlist_id), INDEX IDX_75FFE1E55ED23C43 (track_id), PRIMARY KEY(playlist_id, track_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE track (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, artist VARCHAR(255) NOT NULL, album VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, duration INT DEFAULT NULL, audio_file VARCHAR(255) DEFAULT NULL, cover_image VARCHAR(255) DEFAULT NULL, genre VARCHAR(100) DEFAULT NULL, play_count INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE playlist ADD CONSTRAINT FK_D782112D7E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE playlist_track ADD CONSTRAINT FK_75FFE1E56BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE playlist_track ADD CONSTRAINT FK_75FFE1E55ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user ADD profile_picture VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE playlist DROP FOREIGN KEY FK_D782112D7E3C61F9');
        $this->addSql('ALTER TABLE playlist_track DROP FOREIGN KEY FK_75FFE1E56BBD148');
        $this->addSql('ALTER TABLE playlist_track DROP FOREIGN KEY FK_75FFE1E55ED23C43');
        $this->addSql('DROP TABLE playlist');
        $this->addSql('DROP TABLE playlist_track');
        $this->addSql('DROP TABLE track');
        $this->addSql('ALTER TABLE `user` DROP profile_picture');
    }
}
