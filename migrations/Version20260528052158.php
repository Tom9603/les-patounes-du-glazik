<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260528052158 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invoice ADD signature_token VARCHAR(64) DEFAULT NULL, ADD signature_data LONGTEXT DEFAULT NULL, ADD signed_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_90651744D7605360 ON invoice (signature_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_90651744D7605360 ON invoice');
        $this->addSql('ALTER TABLE invoice DROP signature_token, DROP signature_data, DROP signed_at');
    }
}
