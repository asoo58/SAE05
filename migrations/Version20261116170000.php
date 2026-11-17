<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261116170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligns the vehicle structure index name with Doctrine metadata.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER INDEX idx_vehicule_structure_ref RENAME TO IDX_292FFF1DEF348BCD');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER INDEX IDX_292FFF1DEF348BCD RENAME TO idx_vehicule_structure_ref');
    }
}
