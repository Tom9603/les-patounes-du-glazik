<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260528200125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking ADD animal_species VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX idx_invoice_stripe_pi ON invoice');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking DROP animal_species');
        $this->addSql('CREATE INDEX idx_invoice_stripe_pi ON invoice (stripe_payment_intent_id)');
    }
}
