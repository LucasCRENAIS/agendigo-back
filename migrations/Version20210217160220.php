<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210217160220 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE day CHANGE am_opening am_opening VARCHAR(255) NOT NULL, CHANGE am_closing am_closing VARCHAR(255) DEFAULT NULL, CHANGE pm_opening pm_opening VARCHAR(255) DEFAULT NULL, CHANGE pm_closing pm_closing VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE day CHANGE am_opening am_opening TIME NOT NULL, CHANGE am_closing am_closing TIME DEFAULT NULL, CHANGE pm_opening pm_opening TIME DEFAULT NULL, CHANGE pm_closing pm_closing TIME NOT NULL');
    }
}
