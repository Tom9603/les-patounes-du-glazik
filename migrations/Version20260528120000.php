<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260528120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add index on invoice.stripe_payment_intent_id for faster webhook lookups';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_invoice_stripe_pi ON invoice (stripe_payment_intent_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_invoice_stripe_pi ON invoice');
    }
}
