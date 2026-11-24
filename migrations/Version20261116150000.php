<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Finalizes constraints and removes the empty, obsolete misspelled table. */
final class Version20261116150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Finalizes season, group, and structure constraints after the reference-model migration.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE legacy.etappe_tournee_legacy');
        $this->addSql('ALTER TABLE saison ALTER COLUMN id_structure SET NOT NULL');
        $this->addSql('ALTER INDEX uniq_groupe_code_groupe_ref RENAME TO UNIQ_4B98C217B3FD483');
        $this->addSql('ALTER INDEX uniq_structure_siret_ref RENAME TO UNIQ_6F0137EA26E94372');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('The obsolete duplicate table was empty and has been removed.');
    }
}
