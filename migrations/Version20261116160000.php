<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Fills the remaining fields, defaults, and recipe search objects from the reference SQL. */
final class Version20261116160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligns remaining attributes and recipe full-text/vector search with the reference SQL.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE structure ADD COLUMN iban VARCHAR(34) DEFAULT NULL');
        $this->addSql('ALTER TABLE structure RENAME COLUMN raison_social TO raison_sociale');
        $this->addSql('ALTER TABLE structure ALTER COLUMN adresse_gestion DROP NOT NULL');
        $this->addSql('ALTER TABLE structure ALTER COLUMN telephone DROP NOT NULL');
        $this->addSql('ALTER TABLE structure ALTER COLUMN email DROP NOT NULL');
        $this->addSql('ALTER TABLE structure ALTER COLUMN point_depart_lat DROP NOT NULL');
        $this->addSql('ALTER TABLE structure ALTER COLUMN point_depart_lng DROP NOT NULL');

        $this->addSql('ALTER TABLE adherent RENAME COLUMN raison_social TO raison_sociale');
        $this->addSql('ALTER TABLE adherent ALTER COLUMN civilite DROP NOT NULL');
        $this->addSql('ALTER TABLE adherent ALTER COLUMN ville TYPE VARCHAR(100)');

        $this->addSql('ALTER TABLE depot ALTER COLUMN ville TYPE VARCHAR(100)');
        $this->addSql('ALTER TABLE depot ALTER COLUMN referent_nom DROP NOT NULL');
        $this->addSql('ALTER TABLE depot ALTER COLUMN referent_tel DROP NOT NULL');

        $this->addSql('ALTER TABLE vehicule RENAME COLUMN nom_model TO nom_modele');
        $this->addSql('ALTER TABLE vehicule ADD COLUMN id_structure INT DEFAULT NULL');
        $this->addSql('ALTER TABLE vehicule ALTER COLUMN id_structure SET NOT NULL');
        $this->addSql('ALTER TABLE vehicule ADD CONSTRAINT fk_vehicule_structure_ref FOREIGN KEY (id_structure) REFERENCES structure (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_VEHICULE_STRUCTURE_REF ON vehicule (id_structure)');

        $this->addSql('ALTER TABLE utilisateur ALTER COLUMN telephone DROP NOT NULL');
        $this->addSql('ALTER TABLE utilisateur ALTER COLUMN date_creation TYPE TIMESTAMP(0) WITHOUT TIME ZONE USING date_creation::TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('UPDATE utilisateur SET date_creation = CURRENT_TIMESTAMP WHERE date_creation IS NULL');
        $this->addSql('ALTER TABLE utilisateur ALTER COLUMN date_creation SET DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE utilisateur ALTER COLUMN date_creation SET NOT NULL');

        $this->addSql('ALTER TABLE recette ALTER COLUMN nb_personnes SET DEFAULT 4');
        $this->addSql('ALTER TABLE recette ALTER COLUMN partage_national SET DEFAULT true');
        $this->addSql('ALTER TABLE employe ALTER COLUMN actif SET DEFAULT true');
        $this->addSql('UPDATE employe SET actif = true WHERE actif IS NULL');
        $this->addSql('ALTER TABLE employe ALTER COLUMN actif SET NOT NULL');
        $this->addSql('ALTER TABLE calendrier_jour ALTER COLUMN motif_decalage DROP NOT NULL');

        $this->addSql("CREATE OR REPLACE FUNCTION recette_maj_tsv() RETURNS trigger AS \$\$
            BEGIN
                NEW.tsv_recherche :=
                    setweight(to_tsvector('french', coalesce(NEW.titre, '')), 'A') ||
                    setweight(to_tsvector('french', coalesce(NEW.description, '')), 'B') ||
                    setweight(to_tsvector('french', coalesce(NEW.instructions, '')), 'C');
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql");
        $this->addSql('CREATE TRIGGER trg_recette_tsv BEFORE INSERT OR UPDATE OF titre, description, instructions ON recette FOR EACH ROW EXECUTE FUNCTION recette_maj_tsv()');
        $this->addSql('UPDATE recette SET titre = titre');
        $this->addSql('CREATE INDEX idx_recette_tsv ON recette USING GIN (tsv_recherche)');
        $this->addSql('CREATE INDEX idx_recette_embedding ON recette USING hnsw (embedding vector_cosine_ops)');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('The migration adds data defaults and recipe search infrastructure.');
    }
}
