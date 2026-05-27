<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260527234642 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE animal (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, species VARCHAR(255) NOT NULL, breed VARCHAR(100) DEFAULT NULL, birth_date DATE DEFAULT NULL, sex VARCHAR(255) DEFAULT NULL, color VARCHAR(100) DEFAULT NULL, microchip VARCHAR(20) DEFAULT NULL, sterilized TINYINT NOT NULL, health_notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, owner_id INT NOT NULL, INDEX IDX_6AAB231F7E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE booking (id INT AUTO_INCREMENT NOT NULL, service_type VARCHAR(255) NOT NULL, preferred_date DATE NOT NULL, preferred_time VARCHAR(5) DEFAULT NULL, scheduled_at DATETIME DEFAULT NULL, scheduled_end_at DATETIME DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, price NUMERIC(8, 2) DEFAULT NULL, status VARCHAR(255) NOT NULL, client_notes LONGTEXT DEFAULT NULL, admin_notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, client_id INT NOT NULL, animal_id INT DEFAULT NULL, INDEX IDX_E00CEDDE19EB6921 (client_id), INDEX IDX_E00CEDDE8E962C16 (animal_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE animal ADD CONSTRAINT FK_6AAB231F7E3C61F9 FOREIGN KEY (owner_id) REFERENCES member (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE19EB6921 FOREIGN KEY (client_id) REFERENCES member (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE8E962C16 FOREIGN KEY (animal_id) REFERENCES animal (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE animal DROP FOREIGN KEY FK_6AAB231F7E3C61F9');
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDE19EB6921');
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDE8E962C16');
        $this->addSql('DROP TABLE animal');
        $this->addSql('DROP TABLE booking');
    }
}
