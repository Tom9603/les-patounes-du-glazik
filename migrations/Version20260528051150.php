<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260528051150 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE health_record (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(30) NOT NULL, title VARCHAR(255) NOT NULL, notes LONGTEXT DEFAULT NULL, recorded_at DATETIME DEFAULT NULL, next_due_at DATETIME DEFAULT NULL, attachment_filename VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, animal_id INT NOT NULL, INDEX IDX_E0DE77148E962C16 (animal_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE invoice (id INT AUTO_INCREMENT NOT NULL, number VARCHAR(30) NOT NULL, amount NUMERIC(8, 2) NOT NULL, status VARCHAR(20) NOT NULL, stripe_payment_intent_id VARCHAR(255) DEFAULT NULL, stripe_checkout_session_id VARCHAR(255) DEFAULT NULL, paid_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, booking_id INT NOT NULL, UNIQUE INDEX UNIQ_9065174496901F54 (number), INDEX IDX_906517443301C60 (booking_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE health_record ADD CONSTRAINT FK_E0DE77148E962C16 FOREIGN KEY (animal_id) REFERENCES animal (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_906517443301C60 FOREIGN KEY (booking_id) REFERENCES booking (id)');
        $this->addSql('ALTER TABLE member ADD phone VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE health_record DROP FOREIGN KEY FK_E0DE77148E962C16');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_906517443301C60');
        $this->addSql('DROP TABLE health_record');
        $this->addSql('DROP TABLE invoice');
        $this->addSql('ALTER TABLE member DROP phone');
    }
}
