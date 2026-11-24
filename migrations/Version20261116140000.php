<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Completes the reference-model key and relation layout after the initial additive migration. */
final class Version20261116140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligns relation columns, junction primary keys, and legacy table placement with the reference SQL.';
    }

    public function up(Schema $schema): void
    {
        // Retain the misspelled legacy table, but keep it outside the mapped public schema.
        $this->addSql('CREATE SCHEMA IF NOT EXISTS legacy');
        $this->addSql('ALTER TABLE etappe_tournee_legacy SET SCHEMA legacy');

        // These subtype keys are supplied by their parent rows, not generated independently.
        $this->addSql('ALTER TABLE adherent ALTER COLUMN id_adherent DROP IDENTITY');
        $this->addSql('ALTER TABLE employe ALTER COLUMN id_employe DROP IDENTITY');
        $this->addSql('ALTER TABLE chauffeur ALTER COLUMN id_chauffeur DROP IDENTITY');

        // Use the reference names for relation columns and their constraints.
        $this->addSql('ALTER TABLE abonnement DROP CONSTRAINT fk_abonnement_depot_ref');
        $this->addSql('ALTER TABLE abonnement RENAME COLUMN depot_id TO id_depot');
        $this->addSql('ALTER TABLE abonnement ADD CONSTRAINT fk_abonnement_depot_ref FOREIGN KEY (id_depot) REFERENCES depot (id_depot) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_351268BBA911CA8C ON abonnement (id_depot)');

        $this->addSql('ALTER TABLE calendrier_jour DROP CONSTRAINT fk_calendrier_jour_cal_ref');
        $this->addSql('ALTER TABLE calendrier_jour RENAME COLUMN calendrier_id TO id_calendrier');
        $this->addSql('ALTER TABLE calendrier_jour ADD CONSTRAINT FK_A4D1E22317FFBC6E FOREIGN KEY (id_calendrier) REFERENCES calendrier (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_A4D1E22317FFBC6E ON calendrier_jour (id_calendrier)');

        $this->addSql('ALTER TABLE recette DROP CONSTRAINT fk_recette_structure_ref');
        $this->addSql('ALTER TABLE recette RENAME COLUMN structure_origine_id TO id_structure_origine');
        $this->addSql('ALTER TABLE recette ADD CONSTRAINT FK_49BB63903F9EAE49 FOREIGN KEY (id_structure_origine) REFERENCES structure (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_49BB63903F9EAE49 ON recette (id_structure_origine)');

        $this->addSql('ALTER TABLE saison DROP CONSTRAINT fk_saison_structure_ref');
        $this->addSql('ALTER TABLE saison RENAME COLUMN structure_id TO id_structure');
        $this->addSql('ALTER TABLE saison ADD CONSTRAINT FK_C0D0D586EF348BCD FOREIGN KEY (id_structure) REFERENCES structure (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_C0D0D586EF348BCD ON saison (id_structure)');

        $this->addSql('ALTER TABLE type_adhesion DROP CONSTRAINT fk_type_adhesion_structure_ref');
        $this->addSql('ALTER TABLE type_adhesion RENAME COLUMN structure_id TO id_structure');
        $this->addSql('ALTER TABLE type_adhesion ALTER COLUMN id_structure SET NOT NULL');
        $this->addSql('ALTER TABLE type_adhesion ADD CONSTRAINT FK_TYPE_ADHESION_STRUCTURE_REF FOREIGN KEY (id_structure) REFERENCES structure (id) NOT DEFERRABLE');

        $this->addSql('ALTER TABLE utilisateur DROP CONSTRAINT fk_utilisateur_structure_ref');
        $this->addSql('ALTER TABLE utilisateur RENAME COLUMN structure_id TO id_structure');
        $this->addSql('ALTER TABLE utilisateur ALTER COLUMN id_structure SET NOT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_UTILISATEUR_STRUCTURE_REF FOREIGN KEY (id_structure) REFERENCES structure (id) NOT DEFERRABLE');

        $this->addSql('ALTER TABLE tournee DROP CONSTRAINT fk_tournee_structure_ref');
        $this->addSql('ALTER TABLE tournee DROP CONSTRAINT FK_EBF67D7EF965414C');
        $this->addSql('ALTER TABLE tournee DROP CONSTRAINT FK_EBF67D7E4A4A3511');
        $this->addSql('DROP INDEX IF EXISTS IDX_EBF67D7EF965414C');
        $this->addSql('DROP INDEX IF EXISTS IDX_EBF67D7E4A4A3511');
        $this->addSql('DROP INDEX IF EXISTS IDX_EBF67D7E2534008B');
        $this->addSql('ALTER TABLE tournee DROP COLUMN vehicule_id');
        $this->addSql('ALTER TABLE tournee DROP COLUMN saison_id');
        $this->addSql('ALTER TABLE tournee RENAME COLUMN structure_id TO id_structure');
        $this->addSql('ALTER TABLE tournee ALTER COLUMN id_structure SET NOT NULL');
        $this->addSql('ALTER TABLE tournee ADD CONSTRAINT FK_EBF67D7EEF348BCD FOREIGN KEY (id_structure) REFERENCES structure (id) NOT DEFERRABLE');
        $this->addSql('DROP INDEX uniq_tournee_structure_code_ref');
        $this->addSql('CREATE UNIQUE INDEX uniq_tournee_structure_code ON tournee (id_structure, code_tournee)');
        $this->addSql('CREATE INDEX IDX_EBF67D7EEF348BCD ON tournee (id_structure)');

        // Convert etape_tournee from the erroneous surrogate key to the SQL composite key.
        $this->addSql('ALTER TABLE etape_tournee DROP CONSTRAINT fk_b09fdea5f661d013');
        $this->addSql('ALTER TABLE etape_tournee DROP CONSTRAINT etape_tournee_pkey');
        $this->addSql('DROP INDEX uniq_etape_tournee_depot_ref');
        $this->addSql('ALTER TABLE etape_tournee DROP COLUMN id');
        $this->addSql('ALTER TABLE etape_tournee ALTER COLUMN ordre_passage DROP DEFAULT');
        $this->addSql('ALTER TABLE etape_tournee ALTER COLUMN ordre_passage SET NOT NULL');
        $this->addSql('ALTER TABLE etape_tournee ADD CONSTRAINT FK_B09FDEA5356BD40E FOREIGN KEY (id_tournee) REFERENCES tournee (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE etape_tournee ADD PRIMARY KEY (id_tournee, id_depot)');
        $this->addSql('ALTER INDEX idx_b09fdea5f661d013 RENAME TO IDX_B09FDEA5356BD40E');
        $this->addSql('ALTER INDEX idx_b09fdea58510d4de RENAME TO IDX_B09FDEA5A911CA8C');

        // Convert recette_ingredient to its composite key and reference column names.
        $this->addSql('ALTER TABLE recette_ingredient DROP CONSTRAINT fk_ri_ingredient_ref');
        $this->addSql('ALTER TABLE recette_ingredient DROP CONSTRAINT fk_ri_recette_ref');
        $this->addSql('ALTER TABLE recette_ingredient DROP CONSTRAINT recette_ingredient_pkey');
        $this->addSql('DROP INDEX uniq_recette_ingredient_ref');
        $this->addSql('ALTER TABLE recette_ingredient RENAME COLUMN recette_id TO id_recette');
        $this->addSql('ALTER TABLE recette_ingredient RENAME COLUMN ingredient_id TO id_ingredient');
        $this->addSql('ALTER TABLE recette_ingredient DROP COLUMN id');
        $this->addSql('ALTER TABLE recette_ingredient ALTER COLUMN id_recette SET NOT NULL');
        $this->addSql('ALTER TABLE recette_ingredient ALTER COLUMN id_ingredient SET NOT NULL');
        $this->addSql('ALTER TABLE recette_ingredient ADD CONSTRAINT FK_17C041A99726CAE0 FOREIGN KEY (id_recette) REFERENCES recette (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE recette_ingredient ADD CONSTRAINT FK_17C041A9CE25F8A7 FOREIGN KEY (id_ingredient) REFERENCES ingredient (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_17C041A99726CAE0 ON recette_ingredient (id_recette)');
        $this->addSql('CREATE INDEX IDX_17C041A9CE25F8A7 ON recette_ingredient (id_ingredient)');
        $this->addSql('ALTER TABLE recette_ingredient ADD PRIMARY KEY (id_recette, id_ingredient)');

        // Remove the old surrogate key from the user/group junction table.
        $this->addSql('ALTER TABLE utilisateur_groupe DROP COLUMN id');

        $this->addSql('ALTER TABLE livraison_tournee ALTER COLUMN chauffeur_id SET NOT NULL');
        $this->addSql('DROP INDEX uniq_groupe_code_ref');
        $this->addSql('DROP INDEX uniq_structure_siret_ref');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_GROUPE_CODE_GROUPE_REF ON groupe (code_groupe)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_STRUCTURE_SIRET_REF ON structure (siret)');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('The migration removes surrogate keys and restructures relation columns.');
    }
}
