<?php

namespace App\DataFixtures;

use App\Entity\Abonnement;
use App\Entity\Adherent;
use App\Entity\Calendrier;
use App\Entity\Produit;
use App\Entity\Utilisateur;
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
        // 1. Création de l'Administrateur
        $user = new Utilisateur();
        $user->setEmail('prof@cocagne.fr');
        $user->setNom('Prof');
        $user->setPrenom('Cocagne');
        $user->setTelephone('0600000000');
        $user->setActif(true);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->hasher->hashPassword($user, 'test'));
        $manager->persist($user);

        // 2. Création d'un Adhérent de test
        $adherent = new Adherent();
        $adherent->setCivilite('Mme');
        $adherent->setAdresse('15 Rue des Jardins');
        $adherent->setCodePostal('88100');
        $adherent->setVille('Saint-Dié-des-Vosges');
        $adherent->setTelephoneSecondaire('0611223344');
        $adherent->setOptinSms(true);
        $adherent->setOptinEmail(true);
        $adherent->setEstProfessionnel(false);
        $adherent->setDispenseAdhesion(false);
        $manager->persist($adherent);

        // 3. Création des Produits (Paniers)
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

        // 4. Création des Calendriers de livraison
        $calendrierHebdo = new Calendrier();
        $calendrierHebdo->setNom('Planning Mardi');
        $calendrierHebdo->setFrequence('hebdomadaire');
        $manager->persist($calendrierHebdo);

        $calendrierMensuel = new Calendrier();
        $calendrierMensuel->setNom('Planning Jeudi Mensuel');
        $calendrierMensuel->setFrequence('mensuel');
        $manager->persist($calendrierMensuel);

        // 5. Création d'un Abonnement de test liant ces entités
        $abonnement = new Abonnement();
        $abonnement->setDateDebut(new \DateTime('2026-11-01'));
        $abonnement->setDateFin(new \DateTime('2027-11-01'));
        $abonnement->setRemisePourcentage('0.00');
        $abonnement->setStatut('actif');
        
        // On lie les objets créés juste au-dessus
        $abonnement->setAdherent($adherent);
        $abonnement->setProduit($produitFamilial);
        $abonnement->setCalendrier($calendrierHebdo);
        
        $manager->persist($abonnement);

        // Envoi de toutes les données en base
        $manager->flush();
    }
}