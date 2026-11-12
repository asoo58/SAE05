<?php

namespace App\DataFixtures;

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
        $user = new Utilisateur();
        $user->setEmail('prof@cocagne.fr');
        $user->setNom('Prof');
        $user->setPrenom('Cocagne');
        $user->setTelephone('0600000000');
        $user->setActif(true);
        $user->setRoles(['ROLE_ADMIN']);
        
        // Hachage sécurisé du mot de passe "test"
        $hashedPassword = $this->hasher->hashPassword($user, 'test');
        $user->setPassword($hashedPassword);

        $manager->persist($user);
        $manager->flush();
    }
}