<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Align product and depot primary-key names with the reference SQL. */
final class Version20261116130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renames the product and depot primary-key columns to the reference names.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE produit RENAME COLUMN id TO id_produit');
        $this->addSql('ALTER TABLE depot RENAME COLUMN id TO id_depot');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE produit RENAME COLUMN id_produit TO id');
        $this->addSql('ALTER TABLE depot RENAME COLUMN id_depot TO id');
    }
}
