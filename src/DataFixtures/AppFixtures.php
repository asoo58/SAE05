<?php

namespace App\DataFixtures;

use App\Entity\Abonnement;
use App\Entity\Adherent;
use App\Entity\Adhesion;
use App\Entity\Calendrier;
use App\Entity\CalendrierJour;
use App\Entity\Chauffeur;
use App\Entity\Depot;
use App\Entity\Employe;
use App\Entity\Groupe;
use App\Entity\Ingredient;
use App\Entity\Produit;
use App\Entity\Recette;
use App\Entity\Reglement;
use App\Entity\Saison;
use App\Entity\Structure;
use App\Entity\Tournee;
use App\Entity\TypeAdhesion;
use App\Entity\Utilisateur;
use App\Entity\Vehicule;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        // 1. ON CRÉE LA STRUCTURE EN TOUT PREMIER
        $structure = new Structure();
        $structure->setNomCommercial('Jardins de Cocagne');
        $structure->setRaisonSocial('Association Cocagne Vosges');
        $structure->setSiegeSocialAdresse('12 Rue de la Terre');
        $structure->setAdresseGestion('12 Rue de la Terre');
        $structure->setTelephone('0329001122');
        $structure->setEmail('contact@cocagne-vosges.fr');
        $structure->setPointDepartLat('48.2833');
        $structure->setPointDepartLng('6.9500');
        $manager->persist($structure);

        // 2. CRÉATION DE L'ADMIN (avec la structure)
        $user = new Utilisateur();
        $user->setEmail('prof@cocagne.fr');
        $user->setNom('Prof');
        $user->setPrenom('Cocagne');
        $user->setTelephone('0600000000');
        $user->setActif(true);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->hasher->hashPassword($user, 'test'));
        $user->setStructure($structure); // <-- AJOUT
        $manager->persist($user);

        // 3. CRÉATION DU COMPTE ADHÉRENT (avec la structure)
        $userAdherent = new Utilisateur();
        $userAdherent->setEmail('client@cocagne.fr');
        $userAdherent->setNom('Martin');
        $userAdherent->setPrenom('Sophie');
        $userAdherent->setTelephone('0611223344');
        $userAdherent->setActif(true);
        $userAdherent->setRoles(['ROLE_USER']);
        $userAdherent->setPassword($this->hasher->hashPassword($userAdherent, 'test'));
        $userAdherent->setStructure($structure); // <-- AJOUT
        
        $manager->persist($userAdherent);
        $manager->flush(); // Le fameux flush intermédiaire pour l'ID

            

        // Adhérent
        $adherent = new Adherent();
        $adherent->setUtilisateur($userAdherent); 
        $adherent->setCivilite('Mme');
        $adherent->setNom('Martin');    
        $adherent->setPrenom('Sophie'); 
        $adherent->setAdresse('15 Rue des Jardins');
        $adherent->setCodePostal('88100');
        $adherent->setVille('Saint-Dié-des-Vosges');
        $adherent->setTelephoneSecondaire('0611223344');
        $adherent->setOptinSms(true);
        $adherent->setOptinEmail(true);
        $adherent->setEstProfessionnel(false);
        $adherent->setDispenseAdhesion(false);
        $manager->persist($adherent);

        // Produits (Paniers)
        $produitSimple = new Produit();
        $produitSimple->setNom('Panier Simple');
        $produitSimple->setUnite('pièce');
        $produitSimple->setPrix('13.80');
        $produitSimple->setMarge('40.00');
        $produitSimple->setEncombrementCagettes(1);
        $manager->persist($produitSimple);

        $produitFamilial = new Produit();
        $produitFamilial->setNom('Panier Familial');
        $produitFamilial->setUnite('pièce');
        $produitFamilial->setPrix('23.70');
        $produitFamilial->setMarge('40.00');
        $produitFamilial->setEncombrementCagettes(2);
        $manager->persist($produitFamilial);

        // Calendriers
        $calendrierHebdo = new Calendrier();
        $calendrierHebdo->setNom('Planning Mardi');
        $calendrierHebdo->setFrequence('hebdomadaire');
        $manager->persist($calendrierHebdo);


        $calendrierJour = new CalendrierJour();
        $calendrierJour->setDatePreparation(new \DateTime('2026-11-23'));
        $calendrierJour->setDateLivraison(new \DateTime('2026-11-24'));
        $calendrierJour->setEstLivrable(true);
        $calendrierJour->setEstDecale(false);
        $calendrierJour->setCalendrier($calendrierHebdo);
        $manager->persist($calendrierJour);

        

        // Saison
        $saisonEte = new Saison();
        $saisonEte->setLibelle('Saison Été 2026');
        $saisonEte->setDateDebut(new \DateTime('2026-05-01'));
        $saisonEte->setDateFin(new \DateTime('2026-10-31'));
        $saisonEte->setStructure($structure);
        $manager->persist($saisonEte);

        // Véhicule
        $vehicule = new Vehicule();
        $vehicule->setImmatriculation('AB-123-CD');
        $vehicule->setNomModel('Renault Kangoo ZE');
        $vehicule->setCapacitePanier(40);
        $vehicule->setActif(true);
        $vehicule->setStructure($structure);
        $manager->persist($vehicule);

        // Chauffeur & Employé
        $chauffeur = new Chauffeur();
        $chauffeur->setNumeroPermis('123456789ABC');
        $manager->persist($chauffeur);

        // ---Création du compte utilisateur de l'employé ---
        $userEmploye = new Utilisateur();
        $userEmploye->setEmail('employe@cocagne.fr');
        $userEmploye->setNom('Dubois');
        $userEmploye->setPrenom('Marc');
        $userEmploye->setTelephone('0622334455');
        $userEmploye->setActif(true);
        $userEmploye->setRoles(['ROLE_USER']);
        $userEmploye->setPassword($this->hasher->hashPassword($userEmploye, 'test'));
        $userEmploye->setStructure($structure); // On n'oublie pas la structure !

        $manager->persist($userEmploye);
        $manager->flush(); // On génère l'ID tout de suite

        // Création de l'employé avec son compte lié ---
        $employe = new Employe();
        $employe->setUtilisateur($userEmploye); // La liaison indispensable
        $employe->setPoste('Préparateur de commandes');
        $employe->setActif(true);
        $manager->persist($employe);

        // Ingrédients & Recettes
        $ingredient = new Ingredient();
        $ingredient->setNom('Carotte Bio');
        $ingredient->setRayon('Légumes racines');
        $manager->persist($ingredient);

        $recette = new Recette();
        $recette->setTitre('Soupe de carottes au cumin');
        $recette->setInstructions('1. Éplucher. 2. Cuire. 3. Mixer.');
        $recette->setNbPersonnes(4);
        $recette->setPartageNational(true);
        $manager->persist($recette);

        // Type Adhésion & Groupe
        $typeAdhesion = new TypeAdhesion();
        $typeAdhesion->setLibelle('Adhésion Standard');
        $typeAdhesion->setMontant('15.00');
        $typeAdhesion->setStructure($structure);
        $manager->persist($typeAdhesion);

        $groupe = new Groupe();
        $groupe->setCodeGroupe('BENE');
        $groupe->setLibelle('Bénévoles');
        $manager->persist($groupe);


        // ==========================================
        // 2. ENTITÉS DÉPENDANTES (Nécessitent les entités ci-dessus)
        // ==========================================

        // Dépôt (lié à Structure)
        $depot = new Depot();
        $depot->setNom('Boutique Bio Centre-ville');
        $depot->setCategorie(1);
        $depot->setLatitude('48.2850');
        $depot->setLongitude('6.9520');
        $depot->setAdresse('5 Place du Marché');
        $depot->setCodePostal('88100');
        $depot->setVille('Saint-Dié-des-Vosges');
        $depot->setCapaciteMaxPaniers(20);
        $depot->setReferentNom('Jean Dupont');
        $depot->setReferentTel('0699887766');
        $depot->setStructure($structure);
        $manager->persist($depot);

        // Adhésion (liée à Adhérent)
        $adhesion = new Adhesion();
        $adhesion->setDateReglement(new \DateTime('2026-01-15'));
        $adhesion->setEstAJour(true);
        $adhesion->setAdherent($adherent);
        $manager->persist($adhesion);

        // Abonnement (lié à Adhérent, Produit, Calendrier)
        $abonnement = new Abonnement();
        $abonnement->setDateDebut(new \DateTime('2026-11-01'));
        $abonnement->setDateFin(new \DateTime('2027-11-01'));
        $abonnement->setRemisePourcentage('0.00');
        $abonnement->setStatut('actif');
        $abonnement->setAdherent($adherent);
        $abonnement->setProduit($produitFamilial);
        $abonnement->setCalendrier($calendrierHebdo);
        $manager->persist($abonnement);

        // Tournée (liée à Véhicule, Saison)
        // Tournée (liée à Véhicule, Saison, Structure)
        $tournee = new Tournee();
        $tournee->setCodeTournee('T-MARDI-01');
        $tournee->setNom('Tournée Centre');
        $tournee->setJourPreparation('Lundi');
        $tournee->setJourLivraison('Mardi');
        $tournee->setCouleur('#2E7D32');
        $tournee->setVehicule($vehicule);
        $tournee->setSaison($saisonEte);
        $tournee->setStructure($structure);
        $manager->persist($tournee);

        // Règlement (lié à Adhérent et Abonnement)
        $reglement = new Reglement();
        $reglement->setDateReglement(new \DateTimeImmutable('2026-10-25'));
        $reglement->setMontant('94.80');
        $reglement->setModeReglement('Prelevement');
        $reglement->setStatut('valide');
        $reglement->setAdherent($adherent);
        $reglement->setAbonnement($abonnement);
        $manager->persist($reglement);



        // ==========================================
        // 3. FLUSH FINAL
        // ==========================================
        
        $manager->flush();
    }
}