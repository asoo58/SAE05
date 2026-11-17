<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Adds the missing links from the ELIK_FREY_SAE reference model. */
final class Version20261116120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds missing foreign-key relations and junction keys from the reference SQL.';
    }

    public function up(Schema $schema): void
    {
        // Keep the migration additive so existing installations retain their data.
        // Convert the three specializations to the shared-primary-key ISA pattern.
        $this->addSql('ALTER TABLE adherent RENAME COLUMN id TO id_adherent');
        $this->addSql('ALTER TABLE employe RENAME COLUMN id TO id_employe');
        $this->addSql('ALTER TABLE chauffeur RENAME COLUMN id TO id_chauffeur');
        // The misspelled legacy table has no tournee/depot link; keep its data archived.
        $this->addSql('ALTER TABLE etappe_tournee RENAME TO etappe_tournee_legacy');
        $this->addSql('ALTER TABLE utilisateur ADD COLUMN IF NOT EXISTS structure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE saison ADD COLUMN IF NOT EXISTS structure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE type_adhesion ADD COLUMN IF NOT EXISTS structure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tournee ADD COLUMN IF NOT EXISTS structure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE abonnement ADD COLUMN IF NOT EXISTS depot_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE adhesion ADD COLUMN IF NOT EXISTS type_adhesion_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE adhesion ADD COLUMN IF NOT EXISTS saison_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE calendrier ADD COLUMN IF NOT EXISTS tournee_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE calendrier_jour ADD COLUMN IF NOT EXISTS calendrier_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE livraison_tournee ADD COLUMN IF NOT EXISTS tournee_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE livraison_tournee ADD COLUMN IF NOT EXISTS vehicule_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE livraison_tournee ADD COLUMN IF NOT EXISTS chauffeur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE panier_livraison ADD COLUMN IF NOT EXISTS livraison_tournee_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE panier_livraison ADD COLUMN IF NOT EXISTS abonnement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE panier_livraison ADD COLUMN IF NOT EXISTS depot_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reglement ADD COLUMN IF NOT EXISTS adhesion_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE recette ADD COLUMN IF NOT EXISTS structure_origine_id INT DEFAULT NULL');

        $this->addSql('ALTER TABLE utilisateur_groupe ADD COLUMN IF NOT EXISTS utilisateur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur_groupe ADD COLUMN IF NOT EXISTS groupe_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur_groupe DROP CONSTRAINT IF EXISTS utilisateur_groupe_pkey');
        $this->addSql('ALTER TABLE utilisateur_groupe ADD CONSTRAINT pk_utilisateur_groupe_ref PRIMARY KEY (utilisateur_id, groupe_id)');

        $this->addSql('ALTER TABLE recette_ingredient ADD COLUMN IF NOT EXISTS recette_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE recette_ingredient ADD COLUMN IF NOT EXISTS ingredient_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE recette_ingredient ALTER COLUMN quantite DROP NOT NULL');
        $this->addSql('ALTER TABLE recette_ingredient ALTER COLUMN unite DROP NOT NULL');

        $this->addSql('ALTER TABLE etape_tournee RENAME COLUMN tournee_id TO id_tournee');
        $this->addSql('ALTER TABLE etape_tournee RENAME COLUMN depot_id TO id_depot');
        $this->addSql('ALTER TABLE etape_tournee ADD COLUMN IF NOT EXISTS ordre_passage INT DEFAULT 0');
        $this->addSql('ALTER TABLE etape_tournee ADD COLUMN IF NOT EXISTS heure_estimee TIME DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_etape_tournee_depot_ref ON etape_tournee (id_tournee, id_depot)');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_recette_ingredient_ref ON recette_ingredient (recette_id, ingredient_id) WHERE recette_id IS NOT NULL AND ingredient_id IS NOT NULL');

        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_tournee_structure_code_ref ON tournee (structure_id, code_tournee) WHERE structure_id IS NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_groupe_code_ref ON groupe (code_groupe)');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_structure_siret_ref ON structure (siret) WHERE siret IS NOT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT fk_utilisateur_structure_ref FOREIGN KEY (structure_id) REFERENCES structure (id) NOT VALID');
        $this->addSql('ALTER TABLE adherent ADD CONSTRAINT fk_adherent_utilisateur_ref FOREIGN KEY (id_adherent) REFERENCES utilisateur (id) ON DELETE CASCADE NOT VALID');
        $this->addSql('ALTER TABLE employe ADD CONSTRAINT fk_employe_utilisateur_ref FOREIGN KEY (id_employe) REFERENCES utilisateur (id) ON DELETE CASCADE NOT VALID');
        $this->addSql('ALTER TABLE chauffeur ADD CONSTRAINT fk_chauffeur_employe_ref FOREIGN KEY (id_chauffeur) REFERENCES employe (id_employe) ON DELETE CASCADE NOT VALID');
        $this->addSql('ALTER TABLE saison ADD CONSTRAINT fk_saison_structure_ref FOREIGN KEY (structure_id) REFERENCES structure (id) NOT VALID');
        $this->addSql('ALTER TABLE type_adhesion ADD CONSTRAINT fk_type_adhesion_structure_ref FOREIGN KEY (structure_id) REFERENCES structure (id) NOT VALID');
        $this->addSql('ALTER TABLE tournee ADD CONSTRAINT fk_tournee_structure_ref FOREIGN KEY (structure_id) REFERENCES structure (id) NOT VALID');
        $this->addSql('ALTER TABLE abonnement ADD CONSTRAINT fk_abonnement_depot_ref FOREIGN KEY (depot_id) REFERENCES depot (id) NOT VALID');
        $this->addSql('ALTER TABLE adhesion ADD CONSTRAINT fk_adhesion_type_ref FOREIGN KEY (type_adhesion_id) REFERENCES type_adhesion (id) NOT VALID');
        $this->addSql('ALTER TABLE adhesion ADD CONSTRAINT fk_adhesion_saison_ref FOREIGN KEY (saison_id) REFERENCES saison (id) NOT VALID');
        $this->addSql('ALTER TABLE calendrier ADD CONSTRAINT fk_calendrier_tournee_ref FOREIGN KEY (tournee_id) REFERENCES tournee (id) NOT VALID');
        $this->addSql('ALTER TABLE calendrier_jour ADD CONSTRAINT fk_calendrier_jour_cal_ref FOREIGN KEY (calendrier_id) REFERENCES calendrier (id) ON DELETE CASCADE NOT VALID');
        $this->addSql('ALTER TABLE livraison_tournee ADD CONSTRAINT fk_livraison_tournee_ref FOREIGN KEY (tournee_id) REFERENCES tournee (id) NOT VALID');
        $this->addSql('ALTER TABLE livraison_tournee ADD CONSTRAINT fk_livraison_vehicule_ref FOREIGN KEY (vehicule_id) REFERENCES vehicule (id) NOT VALID');
        $this->addSql('ALTER TABLE livraison_tournee ADD CONSTRAINT fk_livraison_chauffeur_ref FOREIGN KEY (chauffeur_id) REFERENCES chauffeur (id_chauffeur) NOT VALID');
        $this->addSql('ALTER TABLE panier_livraison ADD CONSTRAINT fk_panier_livraison_ref FOREIGN KEY (livraison_tournee_id) REFERENCES livraison_tournee (id) NOT VALID');
        $this->addSql('ALTER TABLE panier_livraison ADD CONSTRAINT fk_panier_abonnement_ref FOREIGN KEY (abonnement_id) REFERENCES abonnement (id) NOT VALID');
        $this->addSql('ALTER TABLE panier_livraison ADD CONSTRAINT fk_panier_depot_ref FOREIGN KEY (depot_id) REFERENCES depot (id) NOT VALID');
        $this->addSql('ALTER TABLE reglement ADD CONSTRAINT fk_reglement_adhesion_ref FOREIGN KEY (adhesion_id) REFERENCES adhesion (id) NOT VALID');
        $this->addSql('ALTER TABLE recette ADD CONSTRAINT fk_recette_structure_ref FOREIGN KEY (structure_origine_id) REFERENCES structure (id) ON DELETE SET NULL NOT VALID');
        $this->addSql('ALTER TABLE utilisateur_groupe ADD CONSTRAINT fk_ug_user_ref FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE NOT VALID');
        $this->addSql('ALTER TABLE utilisateur_groupe ADD CONSTRAINT fk_ug_group_ref FOREIGN KEY (groupe_id) REFERENCES groupe (id) ON DELETE CASCADE NOT VALID');
        $this->addSql('ALTER TABLE recette_ingredient ADD CONSTRAINT fk_ri_recette_ref FOREIGN KEY (recette_id) REFERENCES recette (id) ON DELETE CASCADE NOT VALID');
        $this->addSql('ALTER TABLE recette_ingredient ADD CONSTRAINT fk_ri_ingredient_ref FOREIGN KEY (ingredient_id) REFERENCES ingredient (id) NOT VALID');
        $this->addSql("ALTER TABLE reglement ADD CONSTRAINT ck_reglement_cible_ref CHECK ((abonnement_id IS NOT NULL AND adhesion_id IS NULL) OR (abonnement_id IS NULL AND adhesion_id IS NOT NULL)) NOT VALID");
        $this->addSql('ALTER TABLE saison ADD CONSTRAINT ck_saison_dates_ref CHECK (date_fin >= date_debut) NOT VALID');
        $this->addSql('ALTER TABLE abonnement ADD CONSTRAINT ck_abonnement_dates_ref CHECK (date_fin >= date_debut) NOT VALID');
        $this->addSql('ALTER TABLE abonnement ADD CONSTRAINT ck_abonnement_remise_ref CHECK (remise_pourcentage BETWEEN 0 AND 100) NOT VALID');
        $this->addSql('ALTER TABLE depot ADD CONSTRAINT ck_depot_capacite_ref CHECK (capacite_max_paniers >= 0) NOT VALID');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT ck_produit_prix_ref CHECK (prix >= 0) NOT VALID');
    }

    public function down(Schema $schema): void
    {
        // Intentionally empty: rolling this migration back would remove newly recorded relationships.
    }
}
